<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PaymentInstructionMail;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomBookedDate;
use App\Models\RoomPrice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\CarbonPeriod;

class BookingAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index(Request $request)
    {
        $query = Booking::with(['user', 'room'])->latest();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->whereHas('user', fn ($u) => $u->where('full_name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
                    ->orWhereHas('room', fn ($r) => $r->where('name', 'like', "%{$q}%"))
                    ->orWhere('id', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('check_in_from')) {
            $query->whereDate('check_in', '>=', $request->check_in_from);
        }

        if ($request->filled('check_in_to')) {
            $query->whereDate('check_in', '<=', $request->check_in_to);
        }

        if ($request->filled('check_out_from')) {
            $query->whereDate('check_out', '>=', $request->check_out_from);
        }

        if ($request->filled('check_out_to')) {
            $query->whereDate('check_out', '<=', $request->check_out_to);
        }

        if ($request->filled('checkin_checkout') && !$request->filled('status')) {
            $query->where('status', 'confirmed');
        }

        $bookings = $query->paginate(15)->withQueryString();

        $counts = [
            'total' => Booking::count(),
            'pending' => Booking::where('status', 'pending')->count(),
            'confirmed' => Booking::where('status', 'confirmed')->count(),
        ];
        return view('admin.bookings.index', compact('bookings', 'counts'));
    }

    public function show(Booking $booking)
    {
        $booking->load(['user', 'room', 'payment', 'logs', 'bookingServices.service', 'surcharges']);

        return view('admin.bookings.show', compact('booking'));
    }

    public function create()
    {
        $rooms = Room::where('status', 'available')
            ->with('roomType')
            ->orderBy('room_number')
            ->get();
        $hotelInfo = \App\Models\HotelInfo::first();
        return view('admin.bookings.create', compact('rooms', 'hotelInfo'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:20',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1',
            'status' => 'required|in:pending,confirmed',
            'payment_method' => 'required|in:cash,bank_transfer,credit_card,momo,zalopay',
            'payment_status' => 'required|in:pending,paid,partial',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_note' => 'nullable|string|max:500',
        ]);

        $room = Room::findOrFail($validated['room_id']);
        $checkIn = new \Carbon\Carbon($validated['check_in']);
        $checkOut = new \Carbon\Carbon($validated['check_out']);

        // Check room availability
        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        $dates = collect();
        foreach ($period as $date) {
            $dates->push($date->toDateString());
        }

        $conflict = RoomBookedDate::where('room_id', $room->id)
            ->whereIn('booked_date', $dates)
            ->exists();

        if ($conflict) {
            return back()->withErrors(['check_in' => 'Phòng đã được đặt trong khoảng thời gian này.'])->withInput();
        }

        // Calculate total price
        $totalPrice = $this->calculateTotalPrice($room, $checkIn, $checkOut);

        DB::beginTransaction();
        try {
            // Find or create user
            $user = User::firstOrCreate(
                ['email' => $validated['email']],
                [
                    'full_name' => $validated['full_name'],
                    'phone' => $validated['phone'] ?? null,
                    'password' => bcrypt(Str::random(12)),
                ]
            );

            // Create booking
            $booking = Booking::create([
                'user_id' => $user->id,
                'room_id' => $room->id,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'guests' => $validated['guests'],
                'total_price' => $totalPrice,
                'status' => $validated['status'],
                'payment_status' => 'pending',
            ]);

            // Create booked dates
            foreach ($dates as $d) {
                RoomBookedDate::create([
                    'room_id' => $room->id,
                    'booked_date' => $d,
                    'booking_id' => $booking->id,
                ]);
            }

            // Log
            \App\Models\BookingLog::create([
                'booking_id' => $booking->id,
                'old_status' => 'new',
                'new_status' => $booking->status,
                'changed_at' => now(),
            ]);

            // Create payment record
            try {
                Payment::create([
                    'booking_id' => $booking->id,
                    'amount' => $validated['amount_paid'] ?? 0,
                    'payment_method' => $validated['payment_method'],
                    'status' => $validated['payment_status'],
                    'transaction_id' => 'ADM' . time() . rand(1000, 9999),
                    'notes' => $validated['payment_note'] ?? null,
                    'paid_at' => $validated['payment_status'] === 'paid' ? now() : ($validated['payment_status'] === 'partial' ? now() : null),
                ]);
            } catch (\Exception $e) {
                // Continue even if payment creation fails
            }

            DB::commit();

            return redirect()->route('admin.bookings.show', $booking)->with('success', 'Tạo đơn đặt phòng thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    protected function calculateTotalPrice(Room $room, \Carbon\Carbon $checkIn, \Carbon\Carbon $checkOut): float
    {
        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        $prices = RoomPrice::where('room_id', $room->id)->get();

        $total = 0;
        foreach ($period as $date) {
            $priceForDate = $room->base_price;

            foreach ($prices as $price) {
                if ($date->betweenIncluded($price->start_date, $price->end_date)) {
                    $priceForDate = $price->price;
                    break;
                }
            }

            $total += (float) $priceForDate;
        }

        return $total;
    }

    public function edit(Booking $booking)
    {
        return view('admin.bookings.edit', compact('booking'));
    }

    public function update(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1|max:' . ($booking->room->max_guests ?? 99),
            'total_price' => 'required|numeric|min:0',
            'status' => 'required|in:pending,confirmed,cancelled,completed',
        ]);

        $old_status = $booking->status;

        DB::beginTransaction();
        try {
            // If check-in or check-out dates changed, update the RoomBookedDate records
            $newCheckIn = new \Carbon\Carbon($validated['check_in']);
            $newCheckOut = new \Carbon\Carbon($validated['check_out']);

            if ($booking->check_in != $newCheckIn->format('Y-m-d') || $booking->check_out != $newCheckOut->format('Y-m-d')) {
                // Delete old booked dates
                RoomBookedDate::where('booking_id', $booking->id)->delete();

                // Create new booked dates
                $period = CarbonPeriod::create($newCheckIn, $newCheckOut->copy()->subDay());
                foreach ($period as $date) {
                    RoomBookedDate::create([
                        'room_id' => $booking->room_id,
                        'booked_date' => $date->toDateString(),
                        'booking_id' => $booking->id,
                    ]);
                }
            }

            $booking->update($validated);

            // If cancelled, release dates
            if ($booking->status === 'cancelled') {
                RoomBookedDate::where('booking_id', $booking->id)->delete();
            }

            // Log status change if status was updated
            if ($old_status !== $booking->status) {
                \App\Models\BookingLog::create([
                    'booking_id' => $booking->id,
                    'old_status' => $old_status,
                    'new_status' => $booking->status,
                    'changed_at' => now(),
                ]);
            }

            DB::commit();

            return redirect()->route('admin.bookings.show', $booking)->with('success', 'Cập nhật đơn đặt phòng thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra, vui lòng thử lại sau.')->withInput();
        }
    }

    public function destroy(Booking $booking)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Chỉ quản trị viên mới được xóa đơn đặt phòng.');
        }
        DB::beginTransaction();
        try {
            // Remove related booked date records first to satisfy FK constraints
            RoomBookedDate::where('booking_id', $booking->id)->delete();

            $booking->delete();

            DB::commit();
            return redirect()->route('admin.bookings.index')->with('success', 'Xóa đơn đặt phòng thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi khi xóa đơn đặt phòng. Vui lòng thử lại sau.');
        }
    }

    public function updateStatus(Request $request, Booking $booking)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled,completed',
        ]);

        $old = $booking->status;
        $booking->status = $request->status;
        $booking->save();

        if ($booking->status === 'cancelled') {
            RoomBookedDate::where('booking_id', $booking->id)->delete();
        }

        \App\Models\BookingLog::create([
            'booking_id' => $booking->id,
            'old_status' => $old,
            'new_status' => $booking->status,
            'changed_at' => now(),
        ]);

        return back()->with('success', 'Cập nhật trạng thái thành công.');
    }

    public function checkIn(Booking $booking)
    {
        if ($booking->status !== 'confirmed' || $booking->actual_check_in) {
            return back()->with('error', 'Không thể thực hiện check-in cho đơn này.');
        }

        $old = $booking->status;
        $booking->actual_check_in = now();
        $booking->save();

        \App\Models\BookingLog::create([
            'booking_id' => $booking->id,
            'old_status' => $old,
            'new_status' => 'checked_in',
            'changed_at' => now(),
        ]);

        return back()->with('success', 'Khách đã được check-in.');
    }

    public function checkOut(Booking $booking)
    {
        if (!$booking->actual_check_in || $booking->actual_check_out) {
            return back()->with('error', 'Không thể thực hiện check-out cho đơn này.');
        }

        $old = $booking->status;
        $booking->actual_check_out = now();
        // mark completed on checkout
        $booking->status = 'completed';
        $booking->save();

        \App\Models\BookingLog::create([
            'booking_id' => $booking->id,
            'old_status' => $old,
            'new_status' => 'completed',
            'changed_at' => now(),
        ]);

        return back()->with('success', 'Khách đã check-out.');
    }

    // ===================== MULTI-ROOM BOOKING =====================

    public function createMulti()
    {
        $hotelInfo = \App\Models\HotelInfo::first();
        return view('admin.bookings.create-multi', compact('hotelInfo'));
    }

    public function checkAvailability(Request $request)
    {
        $checkIn = $request->query('check_in');
        $checkOut = $request->query('check_out');

        if (!$checkIn || !$checkOut) {
            return response()->json(['error' => 'Thiếu ngày check-in hoặc check-out'], 400);
        }

        // Get all rooms with their types (exclude maintenance rooms)
        $rooms = Room::with('roomType')
            ->where('status', 'available') // Only available rooms, exclude maintenance
            ->get();

        // Get booked dates in range
        $period = CarbonPeriod::create($checkIn, \Carbon\Carbon::parse($checkOut)->subDay());
        $dates = collect($period)->map(fn($d) => $d->toDateString())->toArray();

        $bookedRoomIds = RoomBookedDate::whereIn('booked_date', $dates)
            ->pluck('room_id')
            ->unique()
            ->toArray();

        // Group by room type
        $roomTypes = [];
        foreach ($rooms as $room) {
            // Skip if room is already booked
            if (in_array($room->id, $bookedRoomIds)) {
                continue;
            }

            $typeId = $room->room_type_id;
            if (!isset($roomTypes[$typeId])) {
                $roomTypes[$typeId] = [
                    'room_type_id' => $typeId,
                    'name' => $room->roomType->name ?? 'Không xác định',
                    'description' => $room->roomType->description ?? '',
                    'base_price' => $room->base_price,
                    'max_occupancy' => $room->max_guests,
                    'area' => $room->roomType->area ?? 30,
                    'image' => $room->roomType->image ?? null,
                    'total_count' => 0,
                    'available_count' => 0,
                ];
            }
            $roomTypes[$typeId]['total_count']++;
            $roomTypes[$typeId]['available_count']++;
        }

        // Filter only available room types
        $availableRoomTypes = array_values(array_filter($roomTypes, fn($rt) => $rt['available_count'] > 0));

        return response()->json([
            'rooms' => $availableRoomTypes,
            'nights' => count($dates),
        ]);
    }

    public function validateCoupon(Request $request)
    {
        $code = $request->query('code');
        $coupon = Coupon::where('code', $code)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expired_at')->orWhere('expired_at', '>=', now());
            })
            ->first();

        if (!$coupon) {
            return response()->json(['valid' => false, 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn']);
        }

        return response()->json([
            'valid' => true,
            'discount_percent' => $coupon->discount_percent,
            'message' => "Giảm {$coupon->discount_percent}%"
        ]);
    }

    public function storeMulti(Request $request)
    {
        $validated = $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'rooms' => 'required|array|min:1',
            'rooms.*.room_type_id' => 'required|exists:room_types,id',
            'rooms.*.quantity' => 'required|integer|min:1',
            'rooms.*.adults' => 'required|integer|min:1',
            'rooms.*.children_0_5' => 'nullable|integer|min:0',
            'rooms.*.children_6_11' => 'nullable|integer|min:0',
            'rooms.*.price_per_night' => 'required|numeric|min:0',
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:20',
            'coupon_code' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,bank_transfer',
            'payment_status' => 'nullable|in:pending,paid',
            'amount_paid' => 'nullable|numeric|min:0',
        ]);

        $checkIn = new \Carbon\Carbon($validated['check_in']);
        $checkOut = new \Carbon\Carbon($validated['check_out']);
        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        $dates = collect($period)->map(fn($d) => $d->toDateString())->toArray();

        DB::beginTransaction();
        try {
            // Find or create user
            $user = User::firstOrCreate(
                ['email' => $validated['email']],
                [
                    'full_name' => $validated['full_name'],
                    'phone' => $validated['phone'] ?? null,
                    'password' => bcrypt(Str::random(12)),
                ]
            );

            // Calculate totals with surcharge logic
            $subtotal = 0;
            $totalGuests = 0;
            $calculatedRoomData = []; // Store calculated values for each room type
            foreach ($validated['rooms'] as $roomData) {
                $roomTypeId = $roomData['room_type_id'];
                $room = Room::where('room_type_id', $roomTypeId)->first();
                $roomType = $room?->roomType;

                $basePrice = $roomData['price_per_night'] ?? ($room?->base_price ?? 0);
                $maxAdults = $roomType?->adult_capacity ?? $room?->max_guests ?? 2;
                $maxChildren = $roomType?->child_capacity ?? 1;

                $adults = $roomData['adults'] ?? 1;
                $children05 = $roomData['children_0_5'] ?? 0;
                $children611 = $roomData['children_6_11'] ?? 0;

                // Calculate extra guests
                $extraAdults = max(0, $adults - $maxAdults);
                $totalChildren = $children05 + $children611;
                $chargeableChildren = max(0, $children611 - $maxChildren);

                // Check limit +2
                if ($extraAdults > 2 || ($totalChildren - $maxChildren) > 2) {
                    throw new \Exception("Số lượng người vượt quá giới hạn +2 cho loại phòng");
                }

                // Calculate fees (40% for adults, 30% for children)
                $extraAdultFeePerNight = $extraAdults * (0.4 * $basePrice);
                $childFeePerNight = $chargeableChildren * (0.3 * $basePrice);

                $actualPricePerNight = $basePrice + $extraAdultFeePerNight + $childFeePerNight;
                $roomSubtotal = $actualPricePerNight * $roomData['quantity'] * count($dates);

                $subtotal += $roomSubtotal;
                $totalGuests += $adults * $roomData['quantity'];
                
                // Store calculated values for later use in booking_rooms creation
                $calculatedRoomData[$roomTypeId] = [
                    'actualPricePerNight' => $actualPricePerNight,
                    'roomSubtotalPerRoom' => $actualPricePerNight * count($dates), // Per room subtotal (not divided)
                    'adults' => $adults,
                    'children05' => $children05,
                    'children611' => $children611,
                ];
            }

            $discount = $validated['discount_amount'] ?? 0;
            $totalPrice = max(0, $subtotal - $discount);

            // Create booking
            $booking = Booking::create([
                'user_id' => $user->id,
                'room_id' => null,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'guests' => $totalGuests,
                'adults' => $totalGuests,
                'children' => 0,
                'total_price' => $totalPrice,
                'coupon_code' => $validated['coupon_code'] ?? null,
                'discount_amount' => $discount,
                'status' => 'confirmed',
                'payment_status' => 'pending', // Default pending
                'payment_method' => $validated['payment_method'],
            ]);

            // Create booking_rooms and assign specific rooms
            foreach ($validated['rooms'] as $roomData) {
                $roomTypeId = $roomData['room_type_id'];
                $quantity = $roomData['quantity'];

                $bookedRoomIds = RoomBookedDate::whereIn('booked_date', $dates)
                    ->pluck('room_id')
                    ->unique()
                    ->toArray();

                $availableRooms = Room::where('room_type_id', $roomTypeId)
                    ->where('status', 'available')
                    ->whereNotIn('id', $bookedRoomIds)
                    ->take($quantity)
                    ->get();

                if ($availableRooms->count() < $quantity) {
                    throw new \Exception("Không đủ phòng trống cho loại phòng đã chọn");
                }

                foreach ($availableRooms as $room) {
                    // Use pre-calculated values for this room type
                    $roomType = $room->roomType;
                    $roomTypeId = $roomType->id;
                    $calculated = $calculatedRoomData[$roomTypeId];
                    
                    $booking->bookingRooms()->create([
                        'room_id' => $room->id,
                        'price_per_night' => $calculated['actualPricePerNight'], // Actual price per night with fees
                        'nights' => count($dates),
                        'subtotal' => $calculated['roomSubtotalPerRoom'], // Correct subtotal per room
                        'adults' => $calculated['adults'],
                        'children_0_5' => $calculated['children05'],
                        'children_6_11' => $calculated['children611'],
                    ]);

                    foreach ($dates as $date) {
                        RoomBookedDate::create([
                            'room_id' => $room->id,
                            'booked_date' => $date,
                            'booking_id' => $booking->id,
                        ]);
                    }
                }
            }

            // Log
            \App\Models\BookingLog::create([
                'booking_id' => $booking->id,
                'old_status' => 'new',
                'new_status' => $booking->status,
                'changed_at' => now(),
            ]);

            // Handle payment based on method
            $paymentMethod = $validated['payment_method'];
            $paymentStatus = $validated['payment_status'] ?? 'pending';
            $amountPaid = $validated['amount_paid'] ?? 0;

            if ($paymentMethod === 'cash') {
                // Cash: can be paid or pending
                if ($paymentStatus === 'paid' && $amountPaid > 0) {
                    // Create payment record immediately
                    Payment::create([
                        'booking_id' => $booking->id,
                        'amount' => $amountPaid,
                        'payment_method' => 'cash',
                        'status' => 'paid',
                        'transaction_id' => 'CASH_' . time() . rand(1000, 9999),
                        'notes' => 'Thanh toán tiền mặt - Admin',
                        'paid_at' => now(),
                    ]);

                    // Update booking payment status
                    $booking->update(['payment_status' => 'paid']);
                }
                // If pending, no payment record created yet

                DB::commit();
                return redirect()->route('admin.bookings.show', $booking)
                    ->with('success', 'Tạo đơn đặt phòng thành công! Thanh toán: ' . ($paymentStatus === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán'));

            } elseif ($paymentMethod === 'bank_transfer') {
                // Bank transfer: always pending initially
                // No payment record created yet - will be created when admin confirms

                // Generate VietQR code URL
                $hotelInfo = \App\Models\HotelInfo::first();
                $qrCodeUrl = $this->generateVietQRUrl(
                    $hotelInfo?->bank_account ?? '0326083913',
                    $hotelInfo?->bank_name ?? 'Vietcombank',
                    $booking->total_price,
                    'BOOKING ' . $booking->id
                );

                // Send payment instruction email to customer
                try {
                    \Log::info('Attempting to send payment email to: ' . $user->email);
                    Mail::to($user->email)->send(new PaymentInstructionMail(
                        $booking,
                        $hotelInfo,
                        count($dates),
                        $qrCodeUrl
                    ));
                    \Log::info('Payment email sent successfully to: ' . $user->email);
                } catch (\Exception $e) {
                    // Log error but don't stop the booking process
                    \Log::error('Failed to send payment instruction email: ' . $e->getMessage());
                    \Log::error('Email error trace: ' . $e->getTraceAsString());
                }

                DB::commit();

                // Redirect to payment instruction page
                return redirect()->route('admin.bookings.payment-instruction', $booking)
                    ->with('success', 'Tạo đơn đặt phòng thành công! Email hướng dẫn thanh toán đã được gửi đến khách.');
            }

            DB::commit();
            return redirect()->route('admin.bookings.show', $booking)->with('success', 'Tạo đơn đặt phòng thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Generate VietQR code URL
     */
    protected function generateVietQRUrl(string $accountNumber, string $bankName, float $amount, string $content): string
    {
        // Map bank name to VietQR bank ID
        $bankMap = [
            'Vietcombank' => '970436',
            'Vietinbank' => '970415',
            'BIDV' => '970418',
            'Agribank' => '970405',
            'Techcombank' => '970407',
            'ACB' => '970416',
            'Sacombank' => '970403',
            'VPBank' => '970432',
            'MBBank' => '970422',
            'TPBank' => '970423',
        ];

        $bankId = $bankMap[$bankName] ?? '970436'; // Default to Vietcombank

        // Generate VietQR URL using VietQR API
        $encodedContent = urlencode($content);
        return "https://img.vietqr.io/image/{$bankId}-{$accountNumber}-compact2.png?amount={$amount}&addInfo={$encodedContent}";
    }

    /**
     * Show payment instruction for bank transfer
     */
    public function paymentInstruction(Booking $booking)
    {
        // Load hotel info for bank details
        $hotelInfo = \App\Models\HotelInfo::first();

        // Check if payment already exists
        $existingPayment = Payment::where('booking_id', $booking->id)
            ->where('status', 'paid')
            ->first();

        if ($existingPayment) {
            return redirect()->route('admin.bookings.show', $booking)
                ->with('info', 'Đơn đặt phòng này đã được thanh toán.');
        }

        return view('admin.bookings.payment-instruction', compact('booking', 'hotelInfo'));
    }

    /**
     * Confirm bank transfer payment received
     */
    public function confirmPayment(Request $request, Booking $booking)
    {
        // Validate - chỉ kiểm tra payment_method nếu có
        if ($booking->payment_method && $booking->payment_method !== 'bank_transfer') {
            return back()->withErrors('Phương thức thanh toán không phải chuyển khoản.');
        }

        // Check if already paid
        $existingPayment = Payment::where('booking_id', $booking->id)
            ->where('status', 'paid')
            ->first();

        if ($existingPayment) {
            return back()->withErrors('Đơn này đã được thanh toán trước đó.');
        }

        DB::beginTransaction();
        try {
            // Create payment record
            Payment::create([
                'booking_id' => $booking->id,
                'amount' => $booking->total_price,
                'method' => 'bank_transfer',
                'status' => 'paid',
                'transaction_id' => 'BANK_' . time() . rand(1000, 9999),
                'paid_at' => now(),
            ]);

            // Update booking payment status
            $booking->update([
                'payment_status' => 'paid',
            ]);

            // Log
            \App\Models\BookingLog::create([
                'booking_id' => $booking->id,
                'old_status' => $booking->status,
                'new_status' => 'payment_received',
                'notes' => 'Xác nhận thanh toán chuyển khoản',
                'changed_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('admin.bookings.show', $booking)
                ->with('success', 'Đã xác nhận nhận tiền thành công! Đơn đặt phòng đã được thanh toán.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Cancel booking with reason
     */
    public function cancel(Request $request, Booking $booking)
    {
        // Validate
        if ($booking->status !== 'pending') {
            return back()->withErrors('Chỉ có thể hủy các đơn đang chờ xác nhận.');
        }

        $request->validate([
            'cancel_reason' => 'required|string|max:500',
        ], [
            'cancel_reason.required' => 'Vui lòng nhập lý do hủy đơn.',
            'cancel_reason.max' => 'Lý do hủy không được vượt quá 500 ký tự.',
        ]);

        DB::beginTransaction();
        try {
            // Update booking
            $booking->update([
                'status' => 'cancelled',
                'cancel_reason' => $request->cancel_reason,
                'cancelled_at' => now(),
            ]);

            // Log
            \App\Models\BookingLog::create([
                'booking_id' => $booking->id,
                'old_status' => 'pending',
                'new_status' => 'cancelled',
                'notes' => 'Hủy đơn: ' . $request->cancel_reason,
                'changed_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('admin.bookings.index')
                ->with('success', 'Đã hủy đơn #' . $booking->id . ' thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Store Surcharge/Phiếu phát sinh
     */
    public function storeSurcharge(Request $request, Booking $booking)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $surcharge = $booking->surcharges()->create([
                'reason' => $request->reason,
                'amount' => $request->amount,
            ]);

            // Update total price of the booking
            $booking->total_price += $surcharge->amount;
            $booking->save();

            // Log
            \App\Models\BookingLog::create([
                'booking_id' => $booking->id,
                'old_status' => $booking->status,
                'new_status' => $booking->status,
                'notes' => 'Tạo phiếu phát sinh: ' . $surcharge->reason . ' - ' . number_format($surcharge->amount) . 'đ',
                'changed_at' => now(),
            ]);

            DB::commit();

            return back()->with('success', 'Đã thêm phiếu phát sinh thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi khi tạo phiếu phát sinh: ' . $e->getMessage());
        }
    }
}


