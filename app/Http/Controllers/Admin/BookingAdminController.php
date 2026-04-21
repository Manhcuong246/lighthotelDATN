<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PaymentInstructionMail;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\BookingLog;
use App\Models\Coupon;
use App\Models\Guest;
use App\Models\HotelInfo;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomBookedDate;
use App\Models\RoomType;
use App\Models\Service;
use App\Models\RoomPrice;
use App\Models\User;
use App\Models\BookingService as BookingServiceRow;
use App\Support\BookingInvoiceViewData;
use App\Support\InvoiceExtrasSynchronizer;
use App\Support\RoomOccupancyPricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;

class BookingAdminController extends Controller
{
    /**
     * Initialize controller with admin middleware.
     * Ensures only authenticated admin users can access these routes.
     */
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Display a listing of all bookings with filtering and pagination.
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
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
            'surcharges.service',
            'bookingRooms.room.roomType',
            'bookingGuests.bookingRoom.room',
            'payments',
        ]);
        $latestPayment = $booking->payments->first();
        $services = Service::query()->orderBy('name')->get();

        return view('admin.bookings.show', compact('booking', 'latestPayment', 'services'));
    }

    /**
     * Biên lai / hóa đơn tóm tắt đơn — chỉ khi đã thanh toán và đã checkout (cùng quy tắc khách).
     */
    public function bookingInvoice(Booking $booking)
    {
        if (! BookingInvoiceViewData::customerCanView($booking)) {
            return redirect()
                ->route('admin.bookings.show', $booking)
                ->with('error', 'Chỉ xem biên lai khi đơn đã checkout và đã thanh toán.');
        }

        return view('admin.bookings.invoice', BookingInvoiceViewData::make($booking));
    }

    public function create()
    {
        $rooms = Room::where('status', 'available')
            ->with('roomType')
            ->orderBy('room_number')
            ->get();
        $hotelInfo = HotelInfo::first();
        return view('admin.bookings.create', compact('rooms', 'hotelInfo'));
    }

    public function store(Request $request)
    {
        Log::info('store() called with request data', [
            'all_data' => $request->all(),
            'has_guests_json' => $request->has('guests_json'),
            'guests_json_data' => $request->input('guests_json'),
            'method' => $request->method(),
            'ajax' => $request->ajax()
        ]);

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:20',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'adults' => 'required|integer|min:1',
            'children_6_11' => 'nullable|integer|min:0',
            'children_0_5' => 'nullable|integer|min:0',
            'status' => 'required|in:pending,confirmed',
            'payment_method' => 'required|in:cash,vnpay',
            'payment_status' => 'required|in:pending,paid,partial',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_note' => 'nullable|string|max:500',
            'guests_json' => 'required|json',
        ]);

        // Decode guests JSON
        $guestsArray = json_decode($validated['guests_json'], true);

        // Validate guests data
        if (!is_array($guestsArray) || empty($guestsArray)) {
            return back()->withErrors(['guests_json' => 'Vui lòng nhập thông tin khách hàng.'])->withInput();
        }

        // Validate each guest
        foreach ($guestsArray as $index => $guest) {
            if (empty($guest['name'])) {
                return back()->withErrors(["guests.{$index}.name" => "Tên khách hàng không được để trống."])->withInput();
            }
            if ($index === 0 && empty($guest['cccd'])) {
                return back()->withErrors(["guests.{$index}.cccd" => "CCCD khách hàng chính không được để trống."])->withInput();
            }
        }

        $room = Room::findOrFail($validated['room_id']);
        $checkIn = new \Carbon\Carbon($validated['check_in']);
        $checkOut = new \Carbon\Carbon($validated['check_out']);
        
        $adults = $validated['adults'];
        $children611 = $validated['children_6_11'] ?? 0;
        $children05 = $validated['children_0_5'] ?? 0;
        $totalGuests = $adults + $children611 + $children05;

        $adults = $validated['adults'];
        $children611 = $validated['children_6_11'] ?? 0;
        $children05 = $validated['children_0_5'] ?? 0;
        $totalGuests = $adults + $children611 + $children05;

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

        // Calculate total price with occupancy surcharge
        $totalPrice = $this->calculateTotalPriceWithSurcharge($room, $checkIn, $checkOut, $adults, $children611, $children05);

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
                'guests' => $totalGuests,
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
            BookingLog::create([
                'booking_id' => $booking->id,
                'old_status' => 'new',
                'new_status' => $booking->status,
                'changed_at' => now(),
            ]);

            // Create payment record
            try {
                $ps = $validated['payment_status'];
                $paymentMethod = $validated['payment_method'];
                $paymentPaid = in_array($ps, ['paid', 'partial'], true);

                Payment::create([
                    'booking_id' => $booking->id,
                    'amount' => $validated['amount_paid'] ?? 0,
                    'method' => $paymentMethod,
                    'status' => $paymentPaid ? 'paid' : 'pending',
                    'transaction_id' => 'ADM' . time() . rand(1000, 9999),
                    'paid_at' => $paymentPaid ? now() : null,
                ]);

                // Nếu thanh toán qua VNPay, gửi email cho khách
                if ($paymentMethod === 'vnpay' && $ps === 'pending') {
                    $this->sendVnPayPaymentEmail($booking, $adults, $children611, $children05);
                }
            } catch (\Exception $e) {
                // Continue even if payment creation fails
            }

            // Save guest information
            Log::info('Processing guest data for booking ' . $booking->id, [
                'guests_data' => $guestsArray,
                'is_array' => is_array($guestsArray),
                'count' => count($guestsArray)
            ]);

            if (is_array($guestsArray) && count($guestsArray) > 0) {
                foreach ($guestsArray as $index => $guestData) {
                    Log::info("Creating guest {$index}", [
                        'name' => $guestData['name'] ?? 'MISSING',
                        'cccd' => $guestData['cccd'] ?? 'MISSING',
                    ]);

                    // Determine guest type based on index and counts
                    $guestType = 'adult';
                    if ($index >= $adults) {
                        $guestType = ($index < $adults + $children611) ? 'child_6_11' : 'child_0_5';
                    }

                    BookingGuest::create([
                        'booking_id' => $booking->id,
                        'name' => $guestData['name'] ?? '',
                        'cccd' => $guestData['cccd'] ?? null,
                        'type' => $guestType,
                        'status' => 'pending',
                    ]);
                }
            } else {
                Log::warning('No guest data found in request for booking ' . $booking->id);
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
            $priceForDate = $room->catalogueBasePrice();

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

    /**
     * Tính tổng giá với phụ phí số khách vượt sức chứa tiêu chuẩn
     */
    protected function calculateTotalPriceWithSurcharge(
        Room $room,
        \Carbon\Carbon $checkIn,
        \Carbon\Carbon $checkOut,
        int $adults,
        int $children611 = 0,
        int $children05 = 0
    ): float {
        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        $prices = RoomPrice::where('room_id', $room->id)->get();
        $roomType = $room->roomType;

        $standardCapacity = $roomType->standard_capacity ?? 3;
        $adultSurchargeRate = $roomType->adult_surcharge_rate ?? 0.25;
        $childSurchargeRate = $roomType->child_surcharge_rate ?? 0.125;

        $total = 0;
        foreach ($period as $date) {
            $basePrice = $room->catalogueBasePrice();

            foreach ($prices as $price) {
                if ($date->betweenIncluded($price->start_date, $price->end_date)) {
                    $basePrice = $price->price;
                    break;
                }
            }

            // Tính phụ phí theo RoomOccupancyPricing
            $billableSlots = max(0, $standardCapacity - $children05);
            $extraAdults = max(0, $adults - $billableSlots);
            $remainingSlots = max(0, $billableSlots - $adults);
            $extraChildren611 = max(0, $children611 - $remainingSlots);

            $adultSurcharge = $extraAdults * $adultSurchargeRate * $basePrice;
            $childSurcharge = $extraChildren611 * $childSurchargeRate * $basePrice;

            $total += $basePrice + $adultSurcharge + $childSurcharge;
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
                BookingLog::create([
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

        BookingLog::create([
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
                BookingLog::create([
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
        $booking->actual_check_in = Carbon::now();
        $booking->save();

        // Cập nhật trạng thái tất cả khách hàng thành checked_in
        $booking->guests()->update(['checkin_status' => 'checked_in']);

        // Xóa cache để cập nhật giao diện ngay lập tức
            Cache::forget("guest_info_{$booking->id}");
        BookingLog::create([
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
        $booking->actual_check_out = Carbon::now();
        // mark completed on checkout
        $booking->status = 'completed';
        $booking->save();

        BookingLog::create([
            'booking_id' => $booking->id,
            'old_status' => $old,
            'new_status' => 'completed',
            'changed_at' => Carbon::now(),
        ]);

        return back()->with('success', 'Khách hàng check-out thành công.');
    }

    /**
     * Lấy thông tin khách hàng cho modal check-in
     * Group by booking_rooms
     */
    public function getGuestInfo(Booking $booking)
    {
        try {
            if (!$booking->isAdminCheckinAllowed()) {
                return response()->json(['error' => 'Không thể thực hiện check-in cho đơn này.'], 403);
            }

            $bookingData = $this->loadBookingGuestData($booking);
            $guestsByRoom = $this->buildGuestsByRoomResponse($bookingData);

            if (empty($guestsByRoom)) {
                return $this->createEmptyGuestResponse($booking);
            }

            return $this->createGuestInfoResponse($booking, $guestsByRoom);

        } catch (\Exception $e) {
            return $this->handleGuestInfoError($e, $booking);
        }
    }

    /**
     * Load booking data with relationships for guest info
     */
    private function loadBookingGuestData(Booking $booking): array
    {
        $booking->load([
            'bookingRooms.room.roomType',
            'guests:id,booking_id,name,type,cccd,checkin_status,room_index,room_type,room_id',
            'user:id,full_name',
            'rooms:id,name'
        ]);

        return [
            'booking' => $booking,
            'bookingRooms' => $booking->bookingRooms()->with('room.roomType')->orderBy('id')->get(),
            'allGuests' => $booking->guests()->orderBy('id')->get(),
            'rooms' => $booking->rooms
        ];
    }

    /**
     * Build guests by room response based on booking type
     */
    private function buildGuestsByRoomResponse(array $data): array
    {
        if ($data['bookingRooms']->isEmpty()) {
            return $this->buildLegacyGuestResponse($data['rooms'], $data['allGuests']);
        }

        return $this->buildAssignmentGuestResponse($data['bookingRooms'], $data['allGuests']);
    }

    /**
     * Build response for legacy bookings without booking_rooms
     */
    private function buildLegacyGuestResponse($rooms, $allGuests): array
    {
        $guestsByRoom = [];
        $guestIndex = 0;

        foreach ($rooms as $roomIndex => $room) {
            $roomTypeName = $room->roomType?->name ?? 'Phòng';
            $roomName = $room->name ?? ($roomIndex + 1);
            $roomDisplayName = $roomTypeName . ' ' . $roomName;

            $roomGuests = [];
            $roomGuestCount = ($roomIndex === 0) ? $allGuests->count() : 0;

            for ($i = 0; $i < $roomGuestCount && $guestIndex < $allGuests->count(); $i++) {
                $guest = $allGuests[$guestIndex++];
                $roomGuests[] = $this->formatGuestData($guest);
            }

            if (!empty($roomGuests)) {
                $guestsByRoom[] = $this->formatRoomGroupData($room, $roomDisplayName, $roomTypeName, $roomGuests);
            }
        }

        return $guestsByRoom;
    }

    /**
     * Build response grouped by actual room assignment
     */
    private function buildAssignmentGuestResponse($bookingRooms, $allGuests): array
    {
        $guestsByRoom = [];
        $guestsByAssignedRoom = $allGuests->groupBy(fn($g) => $g->room_id ?? 'unassigned');
        $roomMap = $this->buildRoomMap($bookingRooms);

        foreach ($guestsByAssignedRoom as $roomId => $guests) {
            if ($roomId === 'unassigned') {
                $guestsByRoom[] = $this->buildUnassignedRoomGroup($guests);
            } else {
                $guestsByRoom[] = $this->buildAssignedRoomGroup($roomId, $guests, $roomMap);
            }
        }

        return $guestsByRoom;
    }

    /**
     * Build room map from booking rooms
     */
    private function buildRoomMap($bookingRooms): array
    {
        $roomMap = [];
        foreach ($bookingRooms as $bookingRoom) {
            $room = $bookingRoom->room;
            if ($room) {
                $roomMap[$room->id] = ['room' => $room, 'booking_room' => $bookingRoom];
            }
        }
        return $roomMap;
    }

    /**
     * Build unassigned room group
     */
    private function buildUnassignedRoomGroup($guests): array
    {
        return [
            'room_id' => null,
            'room_name' => 'Chưa gán phòng',
            'room_number' => null,
            'room_type' => null,
            'guests' => $guests->map(fn($g) => $this->formatGuestData($g, false))->toArray()
        ];
    }

    /**
     * Build assigned room group
     */
    private function buildAssignedRoomGroup($roomId, $guests, $roomMap): array
    {
        $roomInfo = $roomMap[$roomId] ?? null;
        $room = $roomInfo['room'] ?? null;
        $roomTypeName = $room?->roomType?->name ?? 'Phòng';
        $roomNumber = $room?->room_number ?? $room?->name ?? '#' . $roomId;

        return [
            'room_id' => $roomId,
            'room_name' => $roomNumber . ' (' . $roomTypeName . ')',
            'room_number' => $roomNumber,
            'room_type' => $roomTypeName,
            'guests' => $guests->map(fn($g) => $this->formatGuestData($g, true))->toArray()
        ];
    }

    /**
     * Format single guest data
     */
    private function formatGuestData($guest, $assigned = null): array
    {
        $isAssigned = $assigned ?? !is_null($guest->room_id);

        return [
            'id' => $guest->id,
            'name' => $guest->name,
            'type' => $guest->type ?? 'adult',
            'cccd' => $guest->cccd ?: '',
            'status' => $guest->checkin_status,
            'room_id' => $guest->room_id,
            'room_assigned' => $isAssigned,
        ];
    }

    /**
     * Format room group data
     */
    private function formatRoomGroupData($room, $roomDisplayName, $roomTypeName, $guests): array
    {
        return [
            'room_id' => $room?->id,
            'room_name' => $roomDisplayName,
            'room_number' => $room?->room_number ?? $room?->name ?? null,
            'room_type' => $room?->roomType?->name ?? $roomTypeName ?? null,
            'guests' => $guests
        ];
    }

    /**
     * Create empty guest response
     */
    private function createEmptyGuestResponse(Booking $booking): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'booking' => [
                'id' => $booking->id,
                'status' => $booking->status,
                'check_in' => $booking->check_in,
                'check_out' => $booking->check_out,
                'user' => $booking->user?->full_name ?? '—',
            ],
            'guests_by_room' => [],
            'message' => 'Không có thông tin khách hàng. Vui lòng thêm thông tin khách hàng.'
        ]);
    }

    /**
     * Create guest info success response
     */
    private function createGuestInfoResponse(Booking $booking, array $guestsByRoom): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'booking' => [
                'id' => $booking->id,
                'user_name' => $booking->user?->full_name ?? '—',
                'rooms' => $booking->rooms->pluck('name')->implode(', '),
                'check_in' => $booking->check_in ? \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y') : '—',
                'check_out' => $booking->check_out ? \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') : '—',
            ],
            'guests_by_room' => $guestsByRoom,
        ]);
    }

    /**
     * Handle guest info error
     */
    private function handleGuestInfoError(\Exception $e, Booking $booking): \Illuminate\Http\JsonResponse
    {
        Log::error('Error in getGuestInfo for booking ' . $booking->id, [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'error' => 'Lỗi server: ' . $e->getMessage(),
            'booking' => [
                'id' => $booking->id,
                'status' => $booking->status,
                'check_in' => $booking->check_in,
                'check_out' => $booking->check_out,
                'user' => $booking->user?->full_name,
            ],
            'guests' => []
        ], 500);
    }

    /**
     * Cập nhật thông tin khách hàng
     */
    public function updateGuestInfo(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'guests' => 'required|array|min:1',
            'guests.*.id' => 'required|exists:guests,id', // Sửa sang bảng guests
            'guests.*.name' => 'required|string|max:150',
            'guests.*.cccd' => 'nullable|string|max:20',
            'guests.*.type' => 'required|in:adult,child',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['guests'] as $guestData) {
                /** @var \App\Models\Guest $guest */
                $guest = Guest::find($guestData['id']);
                if ($guest && $guest->booking_id === $booking->id) {
                    $guest->update([
                        'name' => $guestData['name'],
                        'cccd' => $guestData['cccd'] ?? null,
                        'type' => $guestData['type'],
                    ]);
                }
            }

            DB::commit();

            // Clear cache for this booking to ensure fresh data
            Cache::forget("guest_info_{$booking->id}");

            // Return updated guest list
            return $this->getGuestInfo($booking);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Có lôi xây ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Gán phòng cụ thể cho khách khi check-in
     */
    public function assignGuestToRoom(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'guest_id' => 'required|exists:guests,id',
            'room_id'  => 'required|exists:rooms,id',
        ]);

        DB::beginTransaction();
        try {
            $guest = Guest::find($validated['guest_id']);

            // Verify guest belongs to this booking
            if ($guest->booking_id !== $booking->id) {
                return response()->json(['error' => 'Khách không thuộc đơn đặt này'], 403);
            }

            // Verify room is part of this booking
            $roomExists = $booking->bookingRooms()->where('room_id', $validated['room_id'])->exists();
            if (!$roomExists) {
                return response()->json(['error' => 'Phòng không thuộc đơn đặt này'], 403);
            }

            // Assign room to guest
            $guest->update(['room_id' => $validated['room_id']]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã gán phòng thành công',
                'guest' => [
                    'id' => $guest->id,
                    'name' => $guest->name,
                    'room_display' => $guest->room_display
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error assigning room to guest', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Lấy danh sách phòng trống có thể gán cho khách
     */
    public function getAvailableRoomsForAssignment(Booking $booking)
    {
        try {
            // Get rooms from this booking
            $bookingRooms = $booking->bookingRooms()
                ->with('room.roomType')
                ->get();

            $rooms = $bookingRooms->map(function ($br) {
                $room = $br->room;
                return [
                    'id' => $room->id,
                    'room_number' => $room->room_number ?? $room->name ?? '#' . $room->id,
                    'room_type' => $room->roomType?->name ?? 'Phòng',
                    'status' => $room->status,
                    'max_guests' => $room->catalogueMaxGuests(),
                    'current_guests' => Guest::where('room_id', $room->id)
                        ->whereHas('booking', function ($q) {
                            $q->whereIn('status', ['confirmed', 'checked_in']);
                        })
                        ->count()
                ];
            });

            return response()->json([
                'rooms' => $rooms,
                'booking_id' => $booking->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting available rooms', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Có lỗi xảy ra'], 500);
        }
    }

    // ===================== MULTI-ROOM BOOKING =====================

    public function createMulti()
    {
        $hotelInfo = HotelInfo::first();
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
        $period = CarbonPeriod::create($checkIn, Carbon::parse($checkOut)->subDay());
        $dates = [];
        foreach ($period as $date) {
            $dates[] = $date->toDateString();
        }

        $bookedRoomIds = RoomBookedDate::whereIn('booked_date', $dates)
            ->pluck('room_id')
            ->unique()
            ->toArray();

        // Group by room type
        $roomTypes = [];
        foreach ($rooms as $room) {
            /** @var Room $room */
            // Skip if room is already booked
            if (in_array($room->id, $bookedRoomIds)) {
                continue;
            }

            $typeId = $room->room_type_id;
            if (!isset($roomTypes[$typeId])) {
                $roomTypes[$typeId] = [
                    'room_type_id' => $typeId,
                    'name' => $room->roomType->name ?? 'Không xác định',
                    'description' => strip_tags(html_entity_decode($room->roomType->description ?? '')),
                    'base_price' => $room->catalogueBasePrice(),
                    'max_occupancy' => (int) ($room->roomType->capacity ?? $room->catalogueMaxGuests() ?? 6),
                    'standard_capacity' => (int) ($room->roomType->standard_capacity ?? config('booking.pricing.standard_capacity', 3)),
                    'adult_capacity' => $room->roomType->adult_capacity ?? $room->catalogueMaxGuests() ?? 2,
                    'child_capacity' => $room->roomType->child_capacity ?? 0,
                    'adult_surcharge_rate' => RoomOccupancyPricing::adultSurchargeRate($room->roomType),
                    'child_surcharge_rate' => RoomOccupancyPricing::childSurchargeRate($room->roomType),
                    'area' => $room->roomType->area ?? 30,
                    'image' => $room->roomType ? RoomType::resolveImageUrl($room->roomType->image) : null,
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
        Log::info('storeMulti called with request data', [
            'all_data' => $request->all(),
            'has_guests' => $request->has('guests'),
            'guests_data' => $request->input('guests'),
            'method' => $request->method(),
            'ajax' => $request->ajax()
        ]);

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
            'guests' => 'required|array|min:1',
            'guests.*' => 'array',
        ]);

        $normalizedGuests = $this->flattenGuestPayloadByRoomType($request->input('guests', []));
        if (count($normalizedGuests) === 0) {
            return back()->withErrors(['guests' => 'Vui lòng nhập thông tin khách.'])->withInput();
        }

        foreach ($normalizedGuests as $index => $guestData) {
            $name = trim((string) ($guestData['name'] ?? ''));
            $cccd = trim((string) ($guestData['cccd'] ?? ''));

            if ($name === '') {
                return back()->withErrors(['guests' => "Tên khách thứ " . ($index + 1) . " không được để trống."])->withInput();
            }
            if (! preg_match('/^[0-9]{12}$/', $cccd)) {
                return back()->withErrors(['guests' => "CCCD của khách \"{$name}\" phải gồm đúng 12 chữ số."])->withInput();
            }
        }

        $checkIn = Carbon::parse($validated['check_in']);
        $checkOut = Carbon::parse($validated['check_out']);
        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        $dates = [];
        foreach ($period as $date) {
            $dates[] = $date->toDateString();
        }

        DB::beginTransaction();
        try {
            // 1. Tìm hoặc tạo User
            $user = $this->findOrCreateGuestUser($validated);

            // 2. Tính toán dữ liệu giá và phòng
            $pricingData = $this->calculateMultiRoomData($validated['rooms'], $dates);
            $subtotal = $pricingData['subtotal'];
            $totalGuests = $pricingData['total_guests'];
            $calculatedRoomData = $pricingData['calculatedRoomData'];

            $discount = $validated['discount_amount'] ?? 0;
            $totalPrice = max(0, $subtotal - $discount);

            // 3. Tạo đơn Booking
            $paymentMethodInitial = $validated['payment_method'];

            // Lấy CCCD của người đại diện từ khách đầu tiên
            $representativeCccd = null;
            if (!empty($validated['guests'])) {
                $guestRows = $this->flattenGuestPayloadByRoomType($validated['guests']);
                if (!empty($guestRows) && !empty($guestRows[0]['cccd'])) {
                    $representativeCccd = $guestRows[0]['cccd'];
                }
            }

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
                'cccd' => $representativeCccd,
            ]);

            // 4. Gán phòng cụ thể và lưu ngày đã đặt
            $this->assignRoomsToBooking($booking, $calculatedRoomData, $dates);

            // 5. Lưu thông tin khách hàng (legacy)
            $this->createBookingLegacyGuests($booking, $validated);

            // 6. Ghi log
            BookingLog::create([
                'booking_id' => $booking->id,
                'old_status' => 'new',
                'new_status' => $booking->status,
                'changed_at' => Carbon::now(),
            ]);

            // 7. Xử lý thanh toán
            if ($paymentMethodInitial === 'cash') {
                Payment::create([
                    'booking_id'     => $booking->id,
                    'amount'         => $booking->total_price,
                    'method'         => 'cash',
                    'status'         => 'paid',
                    'transaction_id' => 'CASH_' . Carbon::now()->format('YmdHis') . '_' . $booking->id,
                    'paid_at'        => Carbon::now(),
                ]);

                DB::commit();
                return redirect()->route('admin.bookings.show', $booking)
                    ->with('success', 'Tạo đơn đặt phòng thành công! Đã ghi nhận thanh toán tiền mặt.');
            }

            if ($paymentMethodInitial === 'vnpay') {
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
            Log::error('Payment instruction email failed: '.$e->getMessage(), ['exception' => $e]);

            return false;
        }

        // log / array: Mail "thành công" nhưng không có SMTP — khách không nhận được hộp thư thật.
        return ! in_array(config('mail.default'), ['log', 'array'], true);
    }

    /**
     * Gửi email thanh toán VNPay cho khách khi admin tạo đơn
     */
    protected function sendVnPayPaymentEmail(
        Booking $booking,
        int $adults = 1,
        int $children611 = 0,
        int $children05 = 0
    ): void {
        try {
            // Tạo link VNPay có chữ ký
            $vnpayPayUrl = $this->signedVnPayEntryUrl($booking);

            // Tính số đêm
            $checkIn = new \Carbon\Carbon($booking->check_in);
            $checkOut = new \Carbon\Carbon($booking->check_out);
            $nights = $checkIn->diffInDays($checkOut);

            // Lấy thông tin khách sạn
            $hotelInfo = HotelInfo::first();

            // Gửi email
            $emailSent = $this->sendPaymentInstructionMail(
                $booking,
                $hotelInfo,
                $nights,
                null, // QR code URL (không cần cho VNPay)
                $vnpayPayUrl,
                $booking->user->email
            );

            // Log kết quả
            if ($emailSent) {
                Log::info('VNPay payment email sent successfully', [
                    'booking_id' => $booking->id,
                    'email' => $booking->user->email,
                    'amount' => $booking->total_price,
                ]);
            } else {
                Log::warning('VNPay payment email was not delivered (check MAIL_MAILER config)', [
                    'booking_id' => $booking->id,
                    'email' => $booking->user->email,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send VNPay payment email: '.$e->getMessage(), [
                'exception' => $e,
                'booking_id' => $booking->id,
            ]);
        }
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
        $hotelInfo = HotelInfo::first();

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
            BookingLog::create([
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

            Payment::where('booking_id', $booking->id)
                ->where('status', 'pending')
                ->update(['status' => 'failed']);

            // Log
            BookingLog::create([
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
     * Thêm dịch vụ từ danh mục (booking_services) — giá snapshot theo bảng dịch vụ, cộng vào tổng đơn.
     */
    public function storeBookingServices(Request $request, Booking $booking)
    {
        if ($booking->status === 'cancelled') {
            return back()->with('error', 'Không thêm dịch vụ cho đơn đã hủy.');
        }

        $raw = $request->input('svc_items', []);
        if (! is_array($raw)) {
            $raw = [];
        }

        $filtered = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $sid = $row['service_id'] ?? null;
            $qty = (int) ($row['quantity'] ?? 0);
            if ($sid === null || $sid === '' || $qty < 1) {
                continue;
            }
            $filtered[] = [
                'service_id' => (int) $sid,
                'quantity' => $qty,
            ];
        }

        if (count($filtered) === 0) {
            return back()->with('error', 'Chọn ít nhất một dịch vụ trong danh mục (không để trống ô dịch vụ) và số lượng ≥ 1.');
        }

        if (count($filtered) > 50) {
            return back()->with('error', 'Tối đa 50 dòng dịch vụ mỗi lần gửi.');
        }

        $validator = Validator::make(
            ['svc_items' => $filtered],
            [
                'svc_items' => ['required', 'array', 'min:1'],
                'svc_items.*.service_id' => ['required', 'integer', 'exists:services,id'],
                'svc_items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            ]
        );

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $lines = $validator->validated()['svc_items'];

        if (! Schema::hasTable('booking_services')) {
            return back()->with('error', 'Chưa có bảng booking_services trong database. Chạy: php artisan migrate.')->withInput();
        }

        DB::beginTransaction();
        try {
            $totalAdded = 0.0;
            $logParts = [];

            foreach ($lines as $line) {
                $service = Service::query()->whereKey($line['service_id'])->firstOrFail();
                $qty = (int) $line['quantity'];
                $unit = (float) $service->price;
                $lineTotal = round($unit * $qty, 2);

                BookingServiceRow::create([
                    'booking_id' => $booking->id,
                    'service_id' => $service->id,
                    'quantity' => $qty,
                    'price' => $unit,
                ]);

                $totalAdded += $lineTotal;
                $logParts[] = $service->name . ' × ' . $qty . ' — ' . number_format($lineTotal, 0, ',', '.') . ' ₫';
            }

            $booking->total_price = (float) $booking->total_price + $totalAdded;
            $booking->save();

            $payment = $booking->payments()->orderByDesc('id')->first();
            if ($payment && $payment->status === 'pending') {
                $payment->amount = (float) $payment->amount + $totalAdded;
                $payment->save();
            }

            DB::commit();

            $booking->refresh();
            $invoiceNote = $this->syncInvoiceExtrasIfExists($booking);

            $note = count($logParts) === 1
                ? 'Thêm dịch vụ danh mục: ' . ($logParts[0] ?? '')
                : 'Thêm ' . count($logParts) . ' dịch vụ danh mục: ' . implode(' | ', $logParts);

            try {
                BookingLog::create([
                    'booking_id' => $booking->id,
                    'old_status' => $booking->status,
                    'new_status' => $booking->status,
                    'notes' => $note,
                    'changed_at' => now(),
                ]);
            } catch (\Throwable $logErr) {
                // Không làm mất dữ liệu đã commit
            }

            return redirect()
                ->route('admin.bookings.show', $booking->fresh())
                ->with('success', (count($lines) === 1
                    ? 'Đã thêm dịch vụ từ danh mục.'
                    : 'Đã thêm ' . count($lines) . ' dịch vụ từ danh mục.') . $invoiceNote);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('storeBookingServices failed', [
                'booking_id' => $booking->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Không thêm được dịch vụ: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Một form: lưu đồng thời dịch vụ danh mục và/hoặc phụ phí (tránh mất dữ liệu khi chỉ bấm một nút).
     */
    public function storeBookingExtras(Request $request, Booking $booking)
    {
        if ($booking->status === 'cancelled') {
            return back()->with('error', 'Không thêm dịch vụ/phụ phí cho đơn đã hủy.');
        }

        $rawSvc = $request->input('svc_items', []);
        if (! is_array($rawSvc)) {
            $rawSvc = [];
        }
        $svcFiltered = [];
        foreach ($rawSvc as $row) {
            if (! is_array($row)) {
                continue;
            }
            $sid = $row['service_id'] ?? null;
            $qty = (int) ($row['quantity'] ?? 0);
            if ($sid === null || $sid === '' || $qty < 1) {
                continue;
            }
            $svcFiltered[] = [
                'service_id' => (int) $sid,
                'quantity' => $qty,
            ];
        }

        $rawItems = $request->input('items', []);
        if (! is_array($rawItems)) {
            $rawItems = [];
        }
        $surgeFiltered = [];
        foreach ($rawItems as $row) {
            if (! is_array($row)) {
                continue;
            }
            $reason = trim((string) ($row['reason'] ?? ''));
            $amount = (float) ($row['amount'] ?? 0);
            if ($reason === '' || $amount <= 0) {
                continue;
            }
            $surgeFiltered[] = $row;
        }

        if (count($svcFiltered) === 0 && count($surgeFiltered) === 0) {
            return back()->with('error', 'Chọn ít nhất một dịch vụ danh mục hoặc điền ít nhất một dòng phụ phí (mô tả + số tiền > 0), rồi bấm Lưu.');
        }

        if (count($svcFiltered) > 50) {
            return back()->with('error', 'Tối đa 50 dòng dịch vụ mỗi lần gửi.');
        }
        if (count($surgeFiltered) > 50) {
            return back()->with('error', 'Tối đa 50 dòng phụ phí mỗi lần gửi.');
        }

        $svcLines = [];
        if (count($svcFiltered) > 0) {
            if (! Schema::hasTable('booking_services')) {
                return back()->with('error', 'Chưa có bảng booking_services trong database. Chạy: php artisan migrate (hoặc bật lại migration tạo bảng dịch vụ kèm).')->withInput();
            }

            $vSvc = Validator::make(
                ['svc_items' => $svcFiltered],
                [
                    'svc_items' => ['required', 'array', 'min:1'],
                    'svc_items.*.service_id' => ['required', 'integer', 'exists:services,id'],
                    'svc_items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
                ],
                [
                    'svc_items.*.service_id.exists' => 'Dịch vụ đã chọn không tồn tại trong danh mục (hoặc đã bị xóa). Tải lại trang và chọn lại.',
                    'svc_items.*.quantity.min' => 'Số lượng mỗi dịch vụ phải từ 1 trở lên.',
                ]
            );
            if ($vSvc->fails()) {
                return back()->withErrors($vSvc)->withInput();
            }
            $svcLines = $vSvc->validated()['svc_items'];
        }

        $surgeRows = [];
        if (count($surgeFiltered) > 0) {
            $vSur = Validator::make(
                ['items' => $surgeFiltered],
                [
                    'items' => ['required', 'array', 'min:1'],
                    'items.*.reason' => ['required', 'string', 'max:500'],
                    'items.*.amount' => ['required', 'numeric', 'min:0.01'],
                ],
                [
                    'items.*.reason.required' => 'Mỗi dòng cần có mô tả / lý do (phụ phí, bồi thường…).',
                    'items.*.amount.min' => 'Số tiền mỗi dòng phải lớn hơn 0.',
                ]
            );
            if ($vSur->fails()) {
                return back()->withErrors($vSur)->withInput();
            }
            foreach ($vSur->validated()['items'] as $row) {
                $surgeRows[] = [
                    'reason' => trim((string) $row['reason']),
                    'amount' => (float) $row['amount'],
                ];
            }
        }

        DB::beginTransaction();
        try {
            $booking->refresh();

            $totalAdded = 0.0;
            $svcLogParts = [];
            $surgeLogParts = [];

            foreach ($svcLines as $line) {
                $service = Service::query()->whereKey($line['service_id'])->firstOrFail();
                $qty = (int) $line['quantity'];
                $unit = (float) $service->price;
                $lineTotal = round($unit * $qty, 2);

                BookingServiceRow::create([
                    'booking_id' => $booking->id,
                    'service_id' => $service->id,
                    'quantity' => $qty,
                    'price' => $unit,
                ]);

                $totalAdded += $lineTotal;
                $svcLogParts[] = $service->name . ' × ' . $qty . ' — ' . number_format($lineTotal, 0, ',', '.') . ' ₫';
            }

            foreach ($surgeRows as $row) {
                $surcharge = $booking->surcharges()->create([
                    'service_id' => null,
                    'reason' => $row['reason'],
                    'quantity' => 1,
                    'amount' => $row['amount'],
                ]);
                $totalAdded += (float) $surcharge->amount;
                $surgeLogParts[] = $surcharge->reason . ' — ' . number_format((float) $surcharge->amount, 0, ',', '.') . ' ₫';
            }

            $booking->total_price = (float) $booking->total_price + $totalAdded;
            $booking->save();

            $payment = $booking->payments()->orderByDesc('id')->first();
            if ($payment && $payment->status === 'pending') {
                $payment->amount = (float) $payment->amount + $totalAdded;
                $payment->save();
            }

            DB::commit();

            $booking->refresh();
            $invoiceNote = $this->syncInvoiceExtrasIfExists($booking);

            $noteBits = [];
            if ($svcLogParts !== []) {
                $noteBits[] = count($svcLines) === 1
                    ? 'Thêm dịch vụ danh mục: ' . ($svcLogParts[0] ?? '')
                    : 'Thêm ' . count($svcLines) . ' dịch vụ danh mục: ' . implode(' | ', $svcLogParts);
            }
            if ($surgeLogParts !== []) {
                $c = count($surgeRows);
                $noteBits[] = $c === 1
                    ? 'Phụ phí: ' . ($surgeLogParts[0] ?? '')
                    : 'Phụ phí (' . $c . ' dòng): ' . implode(' | ', $surgeLogParts);
            }

            try {
                BookingLog::create([
                    'booking_id' => $booking->id,
                    'old_status' => $booking->status,
                    'new_status' => $booking->status,
                    'notes' => implode(' — ', $noteBits),
                    'changed_at' => now(),
                ]);
            } catch (\Throwable $logErr) {
                //
            }

            $msgParts = [];
            if (count($svcLines) > 0) {
                $msgParts[] = count($svcLines) === 1 ? '1 dịch vụ danh mục' : count($svcLines) . ' dịch vụ danh mục';
            }
            if (count($surgeRows) > 0) {
                $msgParts[] = count($surgeRows) === 1 ? '1 phụ phí' : count($surgeRows) . ' phụ phí';
            }

            return redirect()
                ->route('admin.bookings.show', $booking->fresh())
                ->with('success', 'Đã lưu: ' . implode(' và ', $msgParts) . '.' . $invoiceNote);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('storeBookingExtras failed', [
                'booking_id' => $booking->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Không lưu được: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Phụ phí / bồi thường phát sinh (không cố định): chỉ mô tả + số tiền — không dùng danh mục dịch vụ.
     */
    public function storeSurcharge(Request $request, Booking $booking)
    {
        if ($booking->status === 'cancelled') {
            return back()->with('error', 'Không thêm phụ phí cho đơn đã hủy.');
        }

        $rows = $this->normalizeSurchargeRows($request);
        if ($rows instanceof \Illuminate\Http\RedirectResponse) {
            return $rows;
        }

        DB::beginTransaction();
        try {
            $totalAdded = 0.0;
            $logParts = [];

            foreach ($rows as $row) {
                $surcharge = $booking->surcharges()->create([
                    'service_id' => null,
                    'reason' => $row['reason'],
                    'quantity' => 1,
                    'amount' => $row['amount'],
                ]);

                $totalAdded += (float) $surcharge->amount;

                $logParts[] = $surcharge->reason . ' — ' . number_format((float) $surcharge->amount, 0, ',', '.') . ' ₫';
            }

            $booking->total_price = (float) $booking->total_price + $totalAdded;
            $booking->save();

            $payment = $booking->payments()->orderByDesc('id')->first();
            if ($payment && $payment->status === 'pending') {
                $payment->amount = (float) $payment->amount + $totalAdded;
                $payment->save();
            }

            DB::commit();

            $booking->refresh();
            $invoiceNote = $this->syncInvoiceExtrasIfExists($booking);

            $count = count($rows);
            $logNote = $count === 1
                ? 'Phụ phí: ' . ($logParts[0] ?? '')
                : 'Phụ phí (' . $count . ' dòng): ' . implode(' | ', $logParts);

            try {
                BookingLog::create([
                    'booking_id' => $booking->id,
                    'old_status' => $booking->status,
                    'new_status' => $booking->status,
                    'notes' => $logNote,
                    'changed_at' => now(),
                ]);
            } catch (\Throwable $logErr) {
                //
            }

            $msg = $count === 1
                ? 'Đã ghi nhận phụ phí.'
                : 'Đã ghi nhận ' . $count . ' khoản phụ phí.';

            return redirect()
                ->route('admin.bookings.show', $booking->fresh())
                ->with('success', $msg . $invoiceNote);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Không ghi nhận được phụ phí: ' . $e->getMessage());
        }
    }

    /**
     * @return list<array{reason: string, amount: float}>|\Illuminate\Http\RedirectResponse
     */
    private function normalizeSurchargeRows(Request $request)
    {
        $items = $request->input('items');

        if (is_array($items) && count($items) > 0) {
            $filtered = [];
            foreach ($items as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $reason = trim((string) ($row['reason'] ?? ''));
                $amount = (float) ($row['amount'] ?? 0);
                if ($reason === '' || $amount <= 0) {
                    continue;
                }
                $filtered[] = $row;
            }

            if (count($filtered) === 0) {
                return back()->with('error', 'Điền ít nhất một dòng: mô tả phụ phí và số tiền lớn hơn 0.');
            }

            if (count($filtered) > 50) {
                return back()->with('error', 'Tối đa 50 dòng phụ phí mỗi lần gửi.');
            }

            $validator = Validator::make(
                ['items' => $filtered],
                [
                    'items' => ['required', 'array', 'min:1'],
                    'items.*.reason' => ['required', 'string', 'max:500'],
                    'items.*.amount' => ['required', 'numeric', 'min:0.01'],
                ],
                [
                    'items.*.reason.required' => 'Mỗi dòng cần có mô tả / lý do (phụ phí, bồi thường…).',
                    'items.*.amount.min' => 'Số tiền mỗi dòng phải lớn hơn 0.',
                ]
            );

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            /** @var list<array<string, mixed>> $clean */
            $clean = [];
            foreach ($validator->validated()['items'] as $row) {
                $clean[] = [
                    'reason' => trim((string) $row['reason']),
                    'amount' => (float) $row['amount'],
                ];
            }

            return $clean;
        }

        $legacy = $request->validate([
            'reason' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0.01',
        ]);

        return [[
            'reason' => trim((string) $legacy['reason']),
            'amount' => (float) $legacy['amount'],
        ]];
    }

    /**
     * Sau khi đơn đã commit — nếu có hóa đơn thì cập nhật dịch vụ/phụ phí trên HĐ theo đơn.
     */
    private function syncInvoiceExtrasIfExists(Booking $booking): string
    {
        $booking->loadMissing('invoice');
        if (! $booking->invoice) {
            return '';
        }

        try {
            InvoiceExtrasSynchronizer::replaceExtrasFromBooking($booking->invoice);

            return ' Đã cập nhật hóa đơn theo đơn.';
        } catch (\Throwable $e) {
            Log::warning('invoice_extras_auto_sync_failed', [
                'booking_id' => $booking->id,
                'invoice_id' => $booking->invoice->id,
                'message' => $e->getMessage(),
            ]);

            return '';
        }
    }
    /**
     * Tìm hoặc tạo User dựa trên thông tin gửi lên
     */
    private function findOrCreateGuestUser(array $validated): User
    {
        $guestEmail = Str::lower(trim((string) $validated['email']));
        $user = User::firstOrCreate(
            ['email' => $guestEmail],
            [
                'full_name' => $validated['full_name'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make(Str::random(12)),
            ]
        );

        $user->forceFill([
            'email' => $guestEmail,
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'] ?? $user->phone,
        ])->save();

        return $user;
    }

    /**
     * Tính toán giá và dữ liệu phòng cho nhiều loại phòng
     */
    private function calculateMultiRoomData(array $roomsData, array $dates): array
    {
        $subtotal = 0;
        $totalGuests = 0;
        $calculatedRoomData = [];

        foreach ($roomsData as $roomIndex => $roomData) {
            $roomTypeId = $roomData['room_type_id'];
            $room = Room::where('room_type_id', $roomTypeId)->where('status', 'available')->first();

            $basePrice = (float) ($room ? $room->catalogueBasePrice() : 0);
            $roomType = $room?->roomType;

            $adults = $roomData['adults'] ?? 1;
            $children05 = $roomData['children_0_5'] ?? 0;
            $children611 = $roomData['children_6_11'] ?? 0;

            RoomOccupancyPricing::validate($adults, $children611, $children05, $roomType);
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

        return [
            'subtotal' => $subtotal,
            'total_guests' => $totalGuests,
            'calculatedRoomData' => $calculatedRoomData
        ];
    }

    /**
     * Gán phòng cụ thể và lưu ngày đã đặt
     */
    private function assignRoomsToBooking(Booking $booking, array $calculatedRoomData, array $dates): void
    {
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
    }

    /**
     * Tạo thông tin khách hàng (legacy)
     */
    private function createBookingLegacyGuests(Booking $booking, array $validated): void
    {
        if (empty($validated['guests'])) {
            return;
        }

        $guestRows = $this->flattenGuestPayloadByRoomType($validated['guests']);

        foreach ($guestRows as $guestData) {
            if (!empty($guestData['name'])) {
                Guest::create([
                    'booking_id' => $booking->id,
                    'room_type' => $guestData['room_type'] ?? null,
                    'name' => $guestData['name'],
                    'cccd' => $guestData['cccd'] ?? null,
                    'type' => $guestData['type'] ?? 'adult',
                    'checkin_status' => 'pending',
                    'room_index' => $guestData['room_index'] ?? 0,
                ]);
            }
        }

        Cache::forget("guest_info_{$booking->id}");
    }

    /**
     * Flatten guest payloads nested by room_type or flat by index.
     *
     * @param array $guests
     * @return array<int, array<string, mixed>>
     */
    private function flattenGuestPayloadByRoomType(array $guests): array
    {
        $rows = [];

        foreach ($guests as $roomKey => $roomGuests) {
            if (!is_array($roomGuests)) {
                continue;
            }

            if (isset($roomGuests['name']) || isset($roomGuests['cccd']) || isset($roomGuests['room_index'])) {
                $rows[] = [
                    'room_type' => null,
                    'room_index' => isset($roomGuests['room_index']) ? (int) $roomGuests['room_index'] : 0,
                    'name' => trim((string) ($roomGuests['name'] ?? '')),
                    'cccd' => trim((string) ($roomGuests['cccd'] ?? '')),
                    'type' => $roomGuests['type'] ?? 'adult',
                ];
                continue;
            }

            foreach ($roomGuests as $guestData) {
                if (!is_array($guestData)) {
                    continue;
                }

                $rows[] = [
                    'room_type' => trim((string) $roomKey),
                    'room_index' => isset($guestData['room_index']) ? (int) $guestData['room_index'] : 0,
                    'name' => trim((string) ($guestData['name'] ?? '')),
                    'cccd' => trim((string) ($guestData['cccd'] ?? '')),
                    'type' => $guestData['type'] ?? 'adult',
                ];
            }
        }

        return $rows;
    }

    /**
     * Toggle trạng thái check-in của một khách hàng
     */
    public function toggleGuestStatus(Guest $guest)
    {
        try {
            $newStatus = ($guest->checkin_status === 'checked_in') ? 'pending' : 'checked_in';
            $guest->update(['checkin_status' => $newStatus]);

            // Xóa cache thông tin khách của đơn này để khi load lại sẽ lấy data mới
            Cache::forget("guest_info_{$guest->booking_id}");

            return response()->json([
                'success' => true,
                'new_status' => $newStatus,
                'message' => 'Đã cập nhật trạng thái khách hàng.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy dữ liệu check-in cho booking
     * Trả về danh sách khách và phòng để gán
     */
    public function getCheckInData(Booking $booking)
    {
        try {
            if (!$booking->isAdminCheckinAllowed()) {
                return response()->json(['error' => 'Không thể thực hiện check-in cho đơn này.'], 403);
            }

            // Load booking với phòng và khách
            $booking->load(['user', 'bookingRooms.room.roomType', 'rooms.roomType', 'bookingGuests.bookingRoom.room', 'guests']);

            // Nếu chưa có khách nào, tự động thêm người đại diện từ user
            if ($booking->bookingGuests->isEmpty()) {
                $nameValue = $booking->user?->full_name ?? $booking->user?->name ?? 'Khách hàng';
                $cccdValue = $booking->user?->cccd ?? $booking->cccd ?? null;

                \App\Models\BookingGuest::create([
                    'booking_id' => $booking->id,
                    'name' => $nameValue,
                    'cccd' => $cccdValue,
                    'type' => 'adult',
                    'status' => 'pending',
                    'is_representative' => 1,
                    'checkin_status' => 'pending',
                ]);

                // Reload lại booking để lấy khách mới tạo
                $booking->load('bookingGuests.bookingRoom.room');
            }

            // Lấy danh sách khách
            $guests = $booking->bookingGuests->map(function ($guest) {
                return [
                    'id' => $guest->id,
                    'name' => $guest->name,
                    'cccd' => $guest->cccd,
                    'type' => $guest->type,
                    'status' => $guest->status,
                    'booking_room_id' => $guest->booking_room_id,
                    'is_representative' => $guest->is_representative,
                    'room_name' => $guest->bookingRoom?->room?->roomType?->name . ' ' . $guest->bookingRoom?->room?->room_number,
                ];
            });

            // Parse dates nếu là string
            $checkIn = $booking->check_in;
            $checkOut = $booking->check_out;
            if (is_string($checkIn)) {
                $checkIn = \Carbon\Carbon::parse($checkIn);
            }
            if (is_string($checkOut)) {
                $checkOut = \Carbon\Carbon::parse($checkOut);
            }

            return response()->json([
                'success' => true,
                'booking' => [
                    'id' => $booking->id,
                    'user' => $booking->user?->only(['id', 'full_name', 'email', 'phone']),
                    'check_in' => $checkIn?->format('d/m/Y'),
                    'check_out' => $checkOut?->format('d/m/Y'),
                ],
                'guests' => $guests,
                'booking_rooms' => $booking->bookingRooms->map(function ($br) {
                    return [
                        'id' => $br->id,
                        'room_id' => $br->room_id,
                        'room_name' => $br->room?->roomType?->name . ' ' . $br->room?->room_number,
                    ];
                }),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Lỗi tải dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xử lý check-in với gán phòng cho từng khách
     */
    public function checkInWithAssignment(Request $request, Booking $booking)
    {
        try {
            if (!$booking->isAdminCheckinAllowed()) {
                return back()->with('error', 'Không thể thực hiện check-in cho đơn này.');
            }

            // Validate dữ liệu
            $validated = $request->validate([
                'guests' => 'required|array',
                'guests.*.name' => 'required|string|max:150',
                'guests.*.cccd' => 'required|string|regex:/^[0-9]{12}$/',
                'guests.*.type' => 'required|in:adult,child',
                'guests.*.booking_room_id' => 'required|exists:booking_rooms,id',
            ], [
                'guests.required' => 'Vui lòng thêm ít nhất 1 khách',
                'guests.*.name.required' => 'Vui lòng nhập tên khách',
                'guests.*.cccd.required' => 'Vui lòng nhập CCCD',
                'guests.*.cccd.regex' => 'CCCD phải có đúng 12 số',
                'guests.*.type.required' => 'Vui lòng chọn loại khách',
                'guests.*.booking_room_id.required' => 'Vui lòng chọn phòng cho tất cả khách',
                'guests.*.booking_room_id.exists' => 'Phòng không tồn tại',
            ]);

            // Kiểm tra tất cả booking_room_id thuộc về booking này
            $bookingRoomIds = $booking->bookingRooms->pluck('id')->toArray();
            foreach ($validated['guests'] as $guestData) {
                if (!in_array($guestData['booking_room_id'], $bookingRoomIds)) {
                    return back()->withErrors(['Phòng được chọn không thuộc đơn đặt này'])->withInput();
                }
            }

            DB::beginTransaction();

            try {
                // Lấy danh sách khách hiện tại
                $existingGuestIds = $booking->bookingGuests->pluck('id')->toArray();
                $processedGuestIds = [];

                // Xử lý từng khách
                foreach ($validated['guests'] as $index => $guestData) {
                    // Kiểm tra xem có phải khách đã tồn tại (có ID trong request)
                    if (isset($guestData['id']) && in_array($guestData['id'], $existingGuestIds)) {
                        // Update khách hiện có
                        $guest = BookingGuest::find($guestData['id']);
                        if ($guest && $guest->booking_id == $booking->id) {
                            $guest->update([
                                'name' => $guestData['name'],
                                'cccd' => $guestData['cccd'],
                                'type' => $guestData['type'],
                                'booking_room_id' => $guestData['booking_room_id'],
                                'status' => 'checked_in',
                            ]);
                            $processedGuestIds[] = $guest->id;
                        }
                    } else {
                        // Tạo khách mới
                        $guest = BookingGuest::create([
                            'booking_id' => $booking->id,
                            'booking_room_id' => $guestData['booking_room_id'],
                            'name' => $guestData['name'],
                            'cccd' => $guestData['cccd'],
                            'type' => $guestData['type'],
                            'status' => 'checked_in',
                        ]);
                        $processedGuestIds[] = $guest->id;
                    }
                }

                // Đảm bảo người đại diện được update (nếu có trong danh sách form)
                $representativeGuestId = $booking->bookingGuests()->where('is_representative', 1)->value('id');
                if ($representativeGuestId && !in_array($representativeGuestId, $processedGuestIds)) {
                    // Nếu người đại diện không được xử lý trong form, tìm và update nó
                    $representative = BookingGuest::find($representativeGuestId);
                    if ($representative) {
                        // Tìm phòng cho người đại diện từ form (dùng phòng đầu tiên nếu chưa có)
                        $representativeRoomId = null;
                        foreach ($validated['guests'] as $guestData) {
                            if (!empty($guestData['booking_room_id'])) {
                                $representativeRoomId = $guestData['booking_room_id'];
                                break;
                            }
                        }

                        $representative->update([
                            'booking_room_id' => $representativeRoomId,
                            'status' => 'checked_in',
                        ]);
                        $processedGuestIds[] = $representativeGuestId;
                    }
                }

                // Xóa khách không còn trong danh sách (nếu có), nhưng không xóa người đại diện
                $guestsToDelete = array_diff($existingGuestIds, $processedGuestIds);
                if (!empty($guestsToDelete)) {
                    // Loại bỏ người đại diện khỏi danh sách xóa
                    $guestsToDelete = array_filter($guestsToDelete, function($id) use ($representativeGuestId) {
                        return $id != $representativeGuestId;
                    });
                    if (!empty($guestsToDelete)) {
                        BookingGuest::whereIn('id', $guestsToDelete)->delete();
                    }
                }

                // Cập nhật trạng thái booking
                $old = $booking->status;
                $booking->status = 'checked_in';
                $booking->actual_check_in = Carbon::now();
                $booking->save();

                // Reload booking để đảm bảo dữ liệu mới nhất
                $booking->refresh();

                // Log
                BookingLog::create([
                    'booking_id' => $booking->id,
                    'old_status' => $old,
                    'new_status' => 'checked_in',
                    'changed_at' => now(),
                ]);

                // Xóa cache
                Cache::forget("guest_info_{$booking->id}");

                DB::commit();

                return back()->with('success', 'Check-in thành công! Đã gán phòng cho ' . count($processedGuestIds) . ' khách.');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return back()->withErrors(['Có lỗi xảy ra: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Xóa khách khỏi booking
     */
    public function deleteBookingGuest(BookingGuest $bookingGuest)
    {
        try {
            $booking = Booking::find($bookingGuest->booking_id);

            // Kiểm tra quyền (admin hoặc staff)
            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            if (!$booking || !$user) {
                return response()->json(['error' => 'Không có quyền'], 403);
            }
            /** @var \Illuminate\Database\Eloquent\Collection $roles */
            $roles = $user->roles();
            $hasRole = $roles->whereIn('name', ['admin', 'staff'])->exists();
            if (!$hasRole) {
                return response()->json(['error' => 'Không có quyền'], 403);
            }

            // Không cho xóa nếu đã check-in
            if ($bookingGuest->status === 'checked_in') {
                return response()->json(['error' => 'Không thể xóa khách đã check-in'], 400);
            }

            $bookingGuest->delete();

            return response()->json(['success' => true, 'message' => 'Đã xóa khách']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }
}

            // 3. Xử lý bảng room_booked_dates
            RoomBookedDate::where('booking_id', $booking->id)
                ->where('room_id', $oldRoomId)
                ->delete();

            $days = [];
            foreach ($period as $date) {
                $days[] = [
                    'room_id' => $newRoom->id,
                    'booking_id' => $booking->id,
                    'booked_date' => $date->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            RoomBookedDate::insert($days);

            // 4. Cập nhật trạng thái bảng rooms
            // Nếu thay đổi cho một phòng hôm nay
            $today = now()->toDateString();
            if ($today >= $booking->check_in && $today < $booking->check_out) {
                Room::where('id', $oldRoomId)->update(['status' => 'maintenance']); 
                $newRoom->update(['status' => 'occupied']);
            }

            // 5. Tính lại Total Price của cả đơn đặt phòng
            $newTotalPrice = $booking->bookingRooms()->sum('subtotal');
            // Cộng thêm các phụ phí hoặc dịch vụ nếu có tính trong total
            $servicesTotal = $booking->bookingServices()->get()->sum(function($bs) {
                return $bs->quantity * $bs->price;
            });
            $surchargesTotal = $booking->surcharges()->sum('amount');
            
            $booking->update([
                'total_price' => $newTotalPrice + $servicesTotal + $surchargesTotal
            ]);

            // 6. Ghi lịch sử đổi phòng
            \App\Models\RoomChangeHistory::create([
                'booking_id' => $booking->id,
                'from_room_id' => $oldRoomId,
                'to_room_id' => $newRoom->id,
                'reason' => $request->reason ?? 'Khách yêu cầu đổi phòng',
                'changed_by' => auth()->id(),
                'changed_at' => now(),
            ]);
            
            // Cập nhật lại thanh toán nếu thiếu tiền hoặc thừa tiền
            $payment = Payment::where('booking_id', $booking->id)->orderByDesc('id')->first();
            if ($payment && in_array($payment->status, ['paid', 'partial'])) {
                // Nếu đổi phòng rẻ hơn hoặc bằng giá tiền thì có thể chuyển tiền thừa thành Hoàn tiền?
                // Đối với admin, có thể chỉ cần cập nhật payment.
                $payment->update([
                    'amount' => $booking->total_price
                ]);
            }

            return back()->with('success', 'Đổi phòng thành công! Số dư phòng cũ đã được ghi nhận.');
        });
    }
}
