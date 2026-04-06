<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PaymentInstructionMail;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\HotelInfo;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomBookedDate;
use App\Models\RoomPrice;
use App\Models\User;
use App\Support\RoomOccupancyPricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
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
        $counts = [
            'total' => Booking::count(),
            'pending' => Booking::where('status', 'pending')->count(),
            'confirmed' => Booking::where('status', 'confirmed')->count(),
        ];

        $query = Booking::with(['user', 'room', 'rooms', 'bookingRooms', 'latestPayment'])->latest();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->whereHas('user', fn ($u) => $u->where('full_name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
                    ->orWhereHas('room', fn ($r) => $r->where('name', 'like', "%{$q}%"))
                    ->orWhere('id', 'like', "%{$q}%")
                    ->orWhereHas('payments', fn ($p) => $p->where('transaction_id', 'like', "%{$q}%"));
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

        $bookings = $query->paginate(15)->withQueryString();

        return view('admin.bookings.index', compact('bookings', 'counts'));
    }

    public function show(Booking $booking)
    {
        $booking->load([
            'user',
            'room',
            'payment',
            'logs',
            'bookingServices.service',
            'surcharges',
            'bookingRooms.room.roomType',
            'payments',
        ]);
        $latestPayment = $booking->payments->first();

        return view('admin.bookings.show', compact('booking', 'latestPayment'));
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
            // Find or create user (đồng bộ họ tên / SĐT khi admin đặt hộ hoặc khách đã có tài khoản shadow)
            $guestEmail = Str::lower(trim((string) $validated['email']));
            $user = User::firstOrCreate(
                ['email' => $guestEmail],
                [
                    'full_name' => $validated['full_name'],
                    'phone' => $validated['phone'] ?? null,
                    'password' => bcrypt(Str::random(12)),
                ]
            );
            $user->forceFill([
                'email' => $guestEmail,
                'full_name' => $validated['full_name'],
                'phone' => $validated['phone'] ?? $user->phone,
            ])->save();

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
                'placed_via' => Booking::PLACED_VIA_ADMIN,
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
                $ps = $validated['payment_status'];
                $paymentPaid = in_array($ps, ['paid', 'partial'], true);
                Payment::create([
                    'booking_id' => $booking->id,
                    'amount' => $validated['amount_paid'] ?? 0,
                    'method' => $validated['payment_method'],
                    'status' => $paymentPaid ? 'paid' : 'pending',
                    'transaction_id' => 'ADM' . time() . rand(1000, 9999),
                    'paid_at' => $paymentPaid ? now() : null,
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
        return redirect()->route('admin.bookings.show', $booking);
    }

    public function update(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'check_in'    => 'required|date',
            'check_out'   => 'required|date|after:check_in',
            'total_price' => 'required|numeric|min:0',
            'status'      => 'required|in:pending,confirmed,cancelled,completed',
        ]);

        $old_status = $booking->status;

        DB::beginTransaction();
        try {
            $newCheckIn  = new \Carbon\Carbon($validated['check_in']);
            $newCheckOut = new \Carbon\Carbon($validated['check_out']);

            if ($booking->check_in != $newCheckIn->format('Y-m-d') || $booking->check_out != $newCheckOut->format('Y-m-d')) {
                RoomBookedDate::where('booking_id', $booking->id)->delete();

                $period  = CarbonPeriod::create($newCheckIn, $newCheckOut->copy()->subDay());
                $roomIds = $booking->bookingRooms()->pluck('room_id');

                foreach ($roomIds as $roomId) {
                    foreach ($period as $date) {
                        RoomBookedDate::create([
                            'room_id'     => $roomId,
                            'booked_date' => $date->toDateString(),
                            'booking_id'  => $booking->id,
                        ]);
                    }
                }
            }

            $booking->update([
                'check_in'    => $validated['check_in'],
                'check_out'   => $validated['check_out'],
                'total_price' => $validated['total_price'],
                'status'      => $validated['status'],
            ]);

            if ($booking->status === 'confirmed' && $old_status === 'pending') {
                $booking->update(['payment_status' => 'paid']);
                $payment = Payment::where('booking_id', $booking->id)->orderByDesc('id')->first();
                if ($payment && $payment->status !== 'paid') {
                    $payment->update([
                        'status'         => 'paid',
                        'paid_at'        => now(),
                        'transaction_id' => $payment->transaction_id ?: strtoupper($payment->method ?? 'MANUAL') . '_' . now()->format('YmdHis') . '_' . $booking->id,
                    ]);
                }
            }

            if ($booking->status === 'cancelled') {
                RoomBookedDate::where('booking_id', $booking->id)->delete();
                Payment::where('booking_id', $booking->id)->where('status', 'pending')->update(['status' => 'failed']);
            }

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
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage())->withInput();
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

        if ($request->status === 'confirmed') {
            $booking->payment_status = 'paid';

            $payment = Payment::where('booking_id', $booking->id)
                ->orderByDesc('id')
                ->first();

            if ($payment && $payment->status !== 'paid') {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'transaction_id' => $payment->transaction_id
                        ?: strtoupper($payment->method ?? 'MANUAL') . '_' . now()->format('YmdHis') . '_' . $booking->id,
                ]);
            } elseif (! $payment) {
                Payment::create([
                    'booking_id'     => $booking->id,
                    'amount'         => $booking->total_price,
                    'method'         => $booking->payment_method ?? 'cash',
                    'status'         => 'paid',
                    'transaction_id' => strtoupper($booking->payment_method ?? 'CASH') . '_' . now()->format('YmdHis') . '_' . $booking->id,
                    'paid_at'        => now(),
                ]);
            }
        }

        if ($request->status === 'cancelled') {
            RoomBookedDate::where('booking_id', $booking->id)->delete();
            Payment::where('booking_id', $booking->id)->where('status', 'pending')->update(['status' => 'failed']);
        }

        $booking->save();

        \App\Models\BookingLog::create([
            'booking_id' => $booking->id,
            'old_status' => $old,
            'new_status' => $booking->status,
            'changed_at' => now(),
        ]);

        return back()->with('success', 'Cập nhật trạng thái thành công.');
    }

    /**
     * Chỉnh trạng thái đơn + thanh toán + đồng bộ bảng payments (nghiệp vụ: ví dụ gửi link VNPay nhưng khách trả tiền mặt).
     */
    public function updatePaymentSettings(Request $request, Booking $booking)
    {
        $request->validate([
            'booking_status' => 'required|in:pending,confirmed,cancelled,completed',
            'payment_status' => 'required|in:pending,paid',
            'payment_method' => 'required|in:cash,vnpay,bank_transfer',
        ], [
            'booking_status.required' => 'Chọn trạng thái đơn.',
            'payment_status.required' => 'Chọn trạng thái thanh toán.',
            'payment_method.required' => 'Chọn phương thức thanh toán.',
        ]);

        if (in_array((string) $booking->payment_status, ['refunded', 'partial_refunded'], true)) {
            return back()->withErrors('Đơn có hoàn tiền — không chỉnh thanh toán ở đây.');
        }

        if ($booking->status === 'cancelled' && $request->input('booking_status') !== 'cancelled') {
            return back()->withErrors(
                'Đơn đã hủy và ngày phòng đã mở. Không khôi phục trạng thái đặt phòng tại đây; hãy tạo đơn mới nếu khách đặt lại.'
            );
        }

        $bookingStatus = $request->input('booking_status');
        $paymentStatus = $request->input('payment_status');
        if (in_array($bookingStatus, ['confirmed', 'completed'], true) && $paymentStatus !== 'paid') {
            return back()->withErrors(
                'Đơn đã xác nhận hoặc hoàn thành lưu trú thì phải chọn «Đã ghi nhận thanh toán» (hoặc đổi tiến trình về «Chờ xác nhận» nếu chưa thu tiền).'
            );
        }
        if ($bookingStatus === 'pending' && $paymentStatus !== 'pending') {
            return back()->withErrors(
                'Tiến trình «Chờ xác nhận» chỉ đi với «Chưa ghi nhận thanh toán». Muốn ghi nhận đã thu tiền, hãy đổi tiến trình sang «Đã xác nhận — giữ phòng».'
            );
        }

        DB::beginTransaction();
        try {
            $oldBookingStatus = $booking->status;

            if ($request->input('booking_status') === 'cancelled' && $booking->status !== 'cancelled') {
                RoomBookedDate::where('booking_id', $booking->id)->delete();
                Payment::where('booking_id', $booking->id)->where('status', 'pending')->update(['status' => 'failed']);
            }

            $booking->status = $request->input('booking_status');
            $booking->payment_status = $request->input('payment_status');
            $booking->payment_method = $request->input('payment_method');
            $booking->save();

            $payment = Payment::where('booking_id', $booking->id)->orderByDesc('id')->first();
            $amount = (float) $booking->total_price;

            if ($request->input('payment_status') === 'paid') {
                $transactionId = null;
                if ($request->input('payment_method') === 'cash') {
                    $transactionId = 'CASH_' . now()->format('YmdHis') . '_' . $booking->id;
                } elseif ($payment && $payment->transaction_id && ! str_starts_with((string) $payment->transaction_id, 'CASH_')) {
                    $transactionId = $payment->transaction_id;
                } else {
                    $transactionId = 'ADMIN_PAID_' . $booking->id . '_' . time();
                }

                if ($payment) {
                    $payment->update([
                        'amount' => $amount,
                        'method' => $request->input('payment_method'),
                        'status' => 'paid',
                        'transaction_id' => $transactionId,
                        'paid_at' => $payment->paid_at ?? now(),
                    ]);
                } else {
                    Payment::create([
                        'booking_id' => $booking->id,
                        'amount' => $amount,
                        'method' => $request->input('payment_method'),
                        'status' => 'paid',
                        'transaction_id' => $transactionId,
                        'paid_at' => now(),
                    ]);
                }
            } else {
                $pendingMethod = $request->input('payment_method');
                $pendingTx = 'PENDING_' . $booking->id . '_' . time();
                if ($payment) {
                    $payment->update([
                        'amount' => $amount,
                        'method' => $pendingMethod,
                        'status' => 'pending',
                        'paid_at' => null,
                        'transaction_id' => $pendingTx,
                    ]);
                } else {
                    Payment::create([
                        'booking_id' => $booking->id,
                        'amount' => $amount,
                        'method' => $pendingMethod,
                        'status' => 'pending',
                        'transaction_id' => $pendingTx,
                    ]);
                }
            }

            if ($oldBookingStatus !== $booking->status) {
                \App\Models\BookingLog::create([
                    'booking_id' => $booking->id,
                    'old_status' => $oldBookingStatus,
                    'new_status' => $booking->status,
                    'changed_at' => now(),
                ]);
            }

            DB::commit();

            $hint = '';
            if ($request->input('payment_method') === 'cash' && $request->input('payment_status') === 'paid') {
                $hint = ' Link VNPay trong email (nếu có) không còn dùng được — đơn đã ghi nhận tiền mặt.';
            }

            return back()->with('success', 'Đã cập nhật trạng thái đơn và thanh toán.' . $hint);
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors('Không lưu được: ' . $e->getMessage());
        }
    }

    public function checkIn(Booking $booking)
    {
        if (!$booking->isAdminCheckinAllowed()) {
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
        if (!$booking->isAdminCheckoutAllowed()) {
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
                    'adult_capacity' => $room->roomType->adult_capacity ?? $room->max_guests ?? 2,
                    'child_capacity' => $room->roomType->child_capacity ?? 0,
                    'adult_surcharge_rate' => RoomOccupancyPricing::adultSurchargeRate($room->roomType),
                    'child_surcharge_rate' => RoomOccupancyPricing::childSurchargeRate($room->roomType),
                    'area' => $room->roomType->area ?? 30,
                    'image' => $room->roomType ? \App\Models\RoomType::resolveImageUrl($room->roomType->image) : null,
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
            'payment_method' => 'required|in:cash,vnpay',
            'payment_status' => 'nullable|in:pending,paid',
            'amount_paid' => 'nullable|numeric|min:0',
        ]);

        $checkIn = new \Carbon\Carbon($validated['check_in']);
        $checkOut = new \Carbon\Carbon($validated['check_out']);
        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        $dates = collect($period)->map(fn($d) => $d->toDateString())->toArray();

        DB::beginTransaction();
        try {
            // Find or create user — cập nhật tên/SĐT khi đặt hộ lại cho cùng email
            $guestEmail = Str::lower(trim((string) $validated['email']));
            $user = User::firstOrCreate(
                ['email' => $guestEmail],
                [
                    'full_name' => $validated['full_name'],
                    'phone' => $validated['phone'] ?? null,
                    'password' => bcrypt(Str::random(12)),
                ]
            );
            $user->forceFill([
                'email' => $guestEmail,
                'full_name' => $validated['full_name'],
                'phone' => $validated['phone'] ?? $user->phone,
            ])->save();

            // Calculate totals with surcharge logic (always from room's actual base_price)
            $subtotal = 0;
            $totalGuests = 0;
            $calculatedRoomData = [];
            foreach ($validated['rooms'] as $roomIndex => $roomData) {
                $roomTypeId = $roomData['room_type_id'];
                $room = Room::where('room_type_id', $roomTypeId)->where('status', 'available')->first();

                $basePrice = (float) ($room?->base_price ?? 0);
                $roomType = $room?->roomType;

                $adults = $roomData['adults'] ?? 1;
                $children05 = $roomData['children_0_5'] ?? 0;
                $children611 = $roomData['children_6_11'] ?? 0;

                RoomOccupancyPricing::validate($adults, $children611, $children05);
                $breakdown = RoomOccupancyPricing::breakdown($basePrice, $adults, $children611, $children05, $roomType);

                $actualPricePerNight = $breakdown['price_per_night'];
                $roomSubtotal = $actualPricePerNight * $roomData['quantity'] * count($dates);

                $subtotal += $roomSubtotal;
                $totalGuests += $adults * $roomData['quantity'];

                $key = $roomTypeId . '_' . $roomIndex;
                $calculatedRoomData[$key] = [
                    'room_type_id' => $roomTypeId,
                    'quantity' => $roomData['quantity'],
                    'actualPricePerNight' => $actualPricePerNight,
                    'roomSubtotalPerRoom' => $actualPricePerNight * count($dates),
                    'adults' => $adults,
                    'children05' => $children05,
                    'children611' => $children611,
                ];
            }

            $discount = $validated['discount_amount'] ?? 0;
            $totalPrice = max(0, $subtotal - $discount);

            // Create booking
            $paymentMethodInitial = $validated['payment_method'];
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
                'status' => $paymentMethodInitial === 'vnpay' ? 'pending' : 'confirmed',
                'payment_status' => $paymentMethodInitial === 'vnpay' ? 'pending' : 'paid',
                'payment_method' => $validated['payment_method'],
                'placed_via' => Booking::PLACED_VIA_ADMIN,
            ]);

            // Create booking_rooms and assign specific rooms
            foreach ($calculatedRoomData as $key => $calculated) {
                $roomTypeId = $calculated['room_type_id'];
                $quantity = $calculated['quantity'];

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
                    $booking->bookingRooms()->create([
                        'room_id' => $room->id,
                        'price_per_night' => $calculated['actualPricePerNight'],
                        'nights' => count($dates),
                        'subtotal' => $calculated['roomSubtotalPerRoom'],
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
                Payment::create([
                    'booking_id'     => $booking->id,
                    'amount'         => $booking->total_price,
                    'method'         => 'cash',
                    'status'         => 'paid',
                    'transaction_id' => 'CASH_' . now()->format('YmdHis') . '_' . $booking->id,
                    'paid_at'        => now(),
                ]);

                DB::commit();
                return redirect()->route('admin.bookings.show', $booking)
                    ->with('success', 'Tạo đơn đặt phòng thành công! Đã ghi nhận thanh toán tiền mặt.');
            }

            if ($paymentMethod === 'vnpay') {
                Payment::create([
                    'booking_id' => $booking->id,
                    'amount' => $booking->total_price,
                    'method' => 'vnpay',
                    'status' => 'pending',
                ]);

                DB::commit();

                $booking->load('user');
                $hotelInfo = HotelInfo::first();
                $payUrl = $this->signedVnPayEntryUrl($booking);
                $mailOk = $this->sendPaymentInstructionMail(
                    $booking,
                    $hotelInfo,
                    count($dates),
                    null,
                    $payUrl,
                    $user->email
                );

                $redirect = redirect()->route('admin.bookings.payment-instruction', $booking)
                    ->with('success', 'Đã tạo đơn.');
                if ($mailOk) {
                    return $redirect->with('info', 'Đã gửi email chứa link thanh toán VNPay tới khách.');
                }

                return $redirect->with(
                    'warning',
                    $this->paymentInstructionMailFailureMessage().' — link VNPay vẫn có trên trang này để sao chép cho khách.'
                );
            }

            DB::commit();
            return redirect()->route('admin.bookings.show', $booking)->with('success', 'Tạo đơn đặt phòng thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    protected function signedVnPayEntryUrl(Booking $booking): string
    {
        $days = max(1, (int) config('vnpay.pay_entry_signed_ttl_days', 14));

        return URL::signedRoute(
            'payment.vnpay.pay',
            ['booking' => $booking->id],
            now()->addDays($days)
        );
    }

    protected function sendPaymentInstructionMail(
        Booking $booking,
        ?HotelInfo $hotelInfo,
        int $nights,
        ?string $qrCodeUrl,
        ?string $vnpayPayUrl,
        string $toEmail
    ): bool {
        try {
            Mail::to($toEmail)->send(new PaymentInstructionMail(
                $booking,
                $hotelInfo,
                $nights,
                $qrCodeUrl,
                $vnpayPayUrl
            ));
        } catch (\Throwable $e) {
            \Log::error('Payment instruction email failed: '.$e->getMessage(), ['exception' => $e]);

            return false;
        }

        // log / array: Mail "thành công" nhưng không có SMTP — khách không nhận được hộp thư thật.
        return ! in_array(config('mail.default'), ['log', 'array'], true);
    }

    protected function paymentInstructionMailFailureMessage(): string
    {
        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            return 'Chưa gửi email thật: MAIL_MAILER đang là '.config('mail.default').' (chỉ ghi log). Đặt MAIL_MAILER=smtp, smtp.gmail.com, App Password trong .env rồi chạy php artisan config:clear';
        }

        return 'Không gửi được email (SMTP/Google chặn: kiểm tra App Password Gmail, bật 2FA). Chi tiết trong storage/logs/laravel.log';
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

        $vnpayPayUrl = null;
        $pendingVnpay = Payment::where('booking_id', $booking->id)
            ->where('method', 'vnpay')
            ->where('status', 'pending')
            ->first();

        if ($pendingVnpay && ($booking->payment_method === null || $booking->payment_method === 'vnpay')) {
            $vnpayPayUrl = $this->signedVnPayEntryUrl($booking);
        }

        return view('admin.bookings.payment-instruction', compact('booking', 'hotelInfo', 'vnpayPayUrl'));
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

            RoomBookedDate::where('booking_id', $booking->id)->delete();

            \App\Models\Payment::where('booking_id', $booking->id)
                ->where('status', 'pending')
                ->update(['status' => 'failed']);

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


