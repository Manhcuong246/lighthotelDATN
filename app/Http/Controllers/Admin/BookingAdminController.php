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
use App\Http\Requests\RoomChangeRequest;
use App\Services\RoomChangeService;
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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): \Illuminate\View\View
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

    /**
     * Display the specified booking details.
     *
     * @param \App\Models\Booking $booking
     * @return \Illuminate\View\View
     */
    public function show(Booking $booking): \Illuminate\View\View
    {
        $booking->load([
            'user',
            'room',
            'payment',
            'logs.user',
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
     *
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function bookingInvoice(Booking $booking): \Illuminate\Http\RedirectResponse|\Illuminate\View\View
    {
        if (! BookingInvoiceViewData::customerCanView($booking)) {
            return redirect()
                ->route('admin.bookings.show', $booking)
                ->with('error', 'Chỉ xem biên lai khi đơn đã checkout và đã thanh toán.');
        }

        return view('admin.bookings.invoice', BookingInvoiceViewData::make($booking));
    }


    /**
     * Store a newly created booking in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
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
            'adults' => 'required|integer|min:1|max:6',
            'children_6_11' => 'nullable|integer|min:0|max:5',
            'children_0_5' => 'nullable|integer|min:0|max:3',
            'status' => 'required|in:pending,confirmed',
            'payment_method' => 'required|in:cash,vnpay',
            'payment_status' => 'required|in:pending,paid,partial',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_note' => 'nullable|string|max:500',
            'guests_json' => 'nullable|json',
            'representative_name' => 'required|string|max:150',
            'representative_cccd' => 'required|string|regex:/^[0-9]{12}$/',
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
            // Guest 0 (representative) CCCD is validated via representative_cccd rule
        }

        $room = Room::findOrFail($validated['room_id']);
        $checkIn = new \Carbon\Carbon($validated['check_in']);
        $checkOut = new \Carbon\Carbon($validated['check_out']);

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
                'adults' => $adults,
                'children' => $children611 + $children05,
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

                    // Guest 0 is the representative
                    $isRepresentative = $index === 0;
                    $guestName = $isRepresentative ? $validated['representative_name'] : ($guestData['name'] ?? '');
                    $guestCccd = $isRepresentative ? $validated['representative_cccd'] : ($guestData['cccd'] ?? null);

                    BookingGuest::create([
                        'booking_id' => $booking->id,
                        'name' => $guestName,
                        'cccd' => $guestCccd,
                        'type' => $guestType,
                        'status' => 'pending',
                        'is_representative' => $isRepresentative ? 1 : 0,
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

    /**
     * Redirect to booking show page (edit not supported directly).
     *
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\RedirectResponse
     */
    public function edit(Booking $booking): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('admin.bookings.show', $booking);
    }

    /**
     * Update the specified booking in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Booking $booking): \Illuminate\Http\RedirectResponse
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

    /**
     * Remove the specified booking from storage.
     *
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Booking $booking): \Illuminate\Http\RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isStaff())) {
            abort(403, 'Chỉ quản trị viên và nhân viên mới được xóa đơn đặt phòng.');
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

    /**
     * Update the booking status.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, Booking $booking): \Illuminate\Http\RedirectResponse
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
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePaymentSettings(Request $request, Booking $booking): \Illuminate\Http\RedirectResponse
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

    /**
     * Check in the booking.
     *
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\RedirectResponse
     */
    public function checkIn(Booking $booking): \Illuminate\Http\RedirectResponse
    {
        if (!$booking->isAdminCheckinAllowed()) {
            return back()->with('error', 'Không thể thực hiện check-in cho đơn này.');
        }

        $old = $booking->status;
        $booking->status = 'checked_in';
        $booking->actual_check_in = Carbon::now();
        $booking->save();

        // Cập nhật trạng thái tất cả khách hàng thành checked_in
        $booking->guests()->update(['checkin_status' => 'checked_in']);

        // Xóa cache để cập nhật giao diện ngay lập tức
        Cache::forget("guest_info_{$booking->id}");

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $staffName = $user?->full_name ?? 'Lễ tân';
        $rooms = $booking->bookingRooms()->with('room')->get()->map(fn($br) => $br->room?->name)->filter()->implode(', ');
        $roomText = $rooms ? " phòng {$rooms}" : '';

        BookingLog::create([
            'booking_id' => $booking->id,
            'user_id' => Auth::id(),
            'old_status' => $old,
            'new_status' => 'checked_in',
            'notes' => "{$staffName} check-in{$roomText}.",
            'changed_at' => now(),
        ]);

        return back()->with('success', 'Khách đã được check-in.');
    }

    /**
     * Check out the booking.
     *
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\RedirectResponse
     */
    public function checkOut(Booking $booking): \Illuminate\Http\RedirectResponse
    {
        if (!$booking->isAdminCheckoutAllowed()) {
            return back()->with('error', 'Không thể thực hiện check-out cho đơn này.');
        }

        $old = $booking->status;
        $booking->actual_check_out = Carbon::now();
        // mark completed on checkout
        $booking->status = 'completed';
        $booking->save();

        // Cập nhật trạng thái tất cả khách thành checked_out
        $booking->guests()->update(['checkin_status' => 'checked_out']);
        $booking->bookingGuests()->update(['status' => 'checked_out', 'checkin_status' => 'checked_out']);

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $staffName = $user?->full_name ?? 'Lễ tân';
        $rooms = $booking->bookingRooms()->with('room')->get()->map(fn($br) => $br->room?->name)->filter()->implode(', ');
        $roomText = $rooms ? " phòng {$rooms}" : '';

        BookingLog::create([
            'booking_id' => $booking->id,
            'user_id' => Auth::id(),
            'old_status' => $old,
            'new_status' => 'completed',
            'notes' => "{$staffName} check-out{$roomText}.",
            'changed_at' => Carbon::now(),
        ]);

        return back()->with('success', 'Khách hàng check-out thành công.');
    }

    /**
     * Lấy thông tin khách hàng cho modal check-in
     * Group by booking_rooms
     *
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGuestInfo(Booking $booking): \Illuminate\Http\JsonResponse
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
     *
     * @param \App\Models\Booking $booking
     * @return array{booking: \App\Models\Booking, bookingRooms: \Illuminate\Database\Eloquent\Collection, allGuests: \Illuminate\Database\Eloquent\Collection, rooms: \Illuminate\Database\Eloquent\Collection}
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
     *
     * @param array{booking: \App\Models\Booking, bookingRooms: \Illuminate\Database\Eloquent\Collection, allGuests: \Illuminate\Database\Eloquent\Collection, rooms: \Illuminate\Database\Eloquent\Collection} $data
     * @return array
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
     *
     * @param \Illuminate\Database\Eloquent\Collection $rooms
     * @param \Illuminate\Database\Eloquent\Collection $allGuests
     * @return array
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
     *
     * @param \Illuminate\Database\Eloquent\Collection $bookingRooms
     * @param \Illuminate\Database\Eloquent\Collection $allGuests
     * @return array
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
     *
     * @param \Illuminate\Database\Eloquent\Collection $bookingRooms
     * @return array<int, array{room: \App\Models\Room, booking_room: \App\Models\BookingRoom}>
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
     *
     * @param \Illuminate\Database\Eloquent\Collection $guests
     * @return array
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
     *
     * @param int $roomId
     * @param \Illuminate\Database\Eloquent\Collection $guests
     * @param array $roomMap
     * @return array
     */
    private function buildAssignedRoomGroup(int $roomId, $guests, array $roomMap): array
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
     *
     * @param \App\Models\Guest $guest
     * @param bool|null $assigned
     * @return array
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
     *
     * @param \App\Models\Room|null $room
     * @param string $roomDisplayName
     * @param string $roomTypeName
     * @param array $guests
     * @return array
     */
    private function formatRoomGroupData($room, string $roomDisplayName, string $roomTypeName, array $guests): array
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
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateGuestInfo(Request $request, Booking $booking): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'guests' => 'required|array|min:1',
            'guests.*.id' => 'required|exists:guests,id',
            'guests.*.name' => 'required|string|max:150',
            'guests.*.cccd' => 'nullable|string|max:20',
            'guests.*.type' => 'required|in:adult,child,child_6_11,child_0_5',
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
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Gán phòng cụ thể cho khách khi check-in
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignGuestToRoom(Request $request, Booking $booking): \Illuminate\Http\JsonResponse
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
     *
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableRoomsForAssignment(Booking $booking): \Illuminate\Http\JsonResponse
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

    /**
     * Show the form for creating a multi-room booking.
     *
     * @return \Illuminate\View\View
     */
    public function createMulti(): \Illuminate\View\View
    {
        $hotelInfo = HotelInfo::first();
        return view('admin.bookings.create-multi', compact('hotelInfo'));
    }

    /**
     * Check room availability for the given date range.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAvailability(Request $request): \Illuminate\Http\JsonResponse
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

    /**
     * Validate coupon code.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateCoupon(Request $request): \Illuminate\Http\JsonResponse
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

    /**
     * Store a newly created multi-room booking in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeMulti(Request $request): \Illuminate\Http\RedirectResponse
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
            'guests' => 'nullable|array',
            'guests.*' => 'array',
            'representative_name' => 'required|string|max:150',
            'representative_cccd' => 'required|string|regex:/^[0-9]{12}$/',
        ]);

        $normalizedGuests = $this->flattenGuestPayloadByRoomType($request->input('guests', []));
        if (count($normalizedGuests) === 0) {
            return back()->withErrors(['guests' => 'Vui lòng nhập thông tin khách.'])->withInput();
        }

        foreach ($normalizedGuests as $index => $guestData) {
            $name = trim((string) ($guestData['name'] ?? ''));
            $cccd = trim((string) ($guestData['cccd'] ?? ''));

            // First guest is representative - use representative fields
            if ($index === 0) {
                $name = $validated['representative_name'];
                $cccd = $validated['representative_cccd'];
                $normalizedGuests[$index]['name'] = $name;
                $normalizedGuests[$index]['cccd'] = $cccd;
            }

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
            $this->createBookingLegacyGuests($booking, $normalizedGuests);

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

    /**
     * Generate signed URL for VNPay payment entry.
     *
     * @param \App\Models\Booking $booking
     * @return string
     */
    protected function signedVnPayEntryUrl(Booking $booking): string
    {
        $days = max(1, (int) config('vnpay.pay_entry_signed_ttl_days', 14));

        return URL::signedRoute(
            'payment.vnpay.pay',
            ['booking' => $booking->id],
            now()->addDays($days)
        );
    }

    /**
     * Send payment instruction email to customer.
     *
     * @param \App\Models\Booking $booking
     * @param \App\Models\HotelInfo|null $hotelInfo
     * @param int $nights
     * @param string|null $qrCodeUrl
     * @param string|null $vnpayPayUrl
     * @param string $toEmail
     * @return bool
     */
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
     *
     * @param \App\Models\Booking $booking
     * @param int $adults
     * @param int $children611
     * @param int $children05
     * @return void
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
                    'user_email' => $booking->user->email,
                ]);
            } else {
                Log::warning('VNPay payment email failed to send', [
                    'booking_id' => $booking->id,
                    'user_email' => $booking->user->email,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Error sending VNPay payment email', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get failure message for payment instruction mail
     *
     * @return string
     */
    protected function paymentInstructionMailFailureMessage(): string
    {
        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            return 'Chưa gửi email thật: MAIL_MAILER đang là '.config('mail.default').' (chỉ ghi log). Đặt MAIL_MAILER=smtp, smtp.gmail.com, App Password trong .env rồi chạy php artisan config:clear';
        }

        return 'Không gửi được email (SMTP/Google chặn: kiểm tra App Password Gmail, bật 2FA). Chi tiết trong storage/logs/laravel.log';
    }

    /**
     * Generate VietQR code URL
     *
     * @param string $accountNumber
     * @param string $bankName
     * @param float $amount
     * @param string $content
     * @return string
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
     *
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
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
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\RedirectResponse
     */
    public function confirmPayment(Request $request, Booking $booking): \Illuminate\Http\RedirectResponse
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
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel(Request $request, Booking $booking): \Illuminate\Http\RedirectResponse
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
     *
     * @param \App\Models\Booking $booking
     * @return string
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
     *
     * @param array{email: string, full_name: string, phone?: string} $validated
     * @return \App\Models\User
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
     *
     * @param array $roomsData
     * @param array $dates
     * @return array{subtotal: float, total_guests: int, calculatedRoomData: array}
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
     *
     * @param \App\Models\Booking $booking
     * @param array $calculatedRoomData
     * @param array $dates
     * @return void
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
     *
     * @param \App\Models\Booking $booking
     * @param array $guestRows
     * @return void
     */
    private function createBookingLegacyGuests(Booking $booking, array $guestRows): void
    {
        if (empty($guestRows)) {
            return;
        }

        foreach ($guestRows as $index => $guestData) {
            if (!empty($guestData['name'])) {
                Guest::create([
                    'booking_id' => $booking->id,
                    'room_type' => $guestData['room_type'] ?? null,
                    'name' => $guestData['name'],
                    'cccd' => $guestData['cccd'] ?? null,
                    'type' => $guestData['type'] ?? 'adult',
                    'checkin_status' => 'pending',
                    'room_index' => $guestData['room_index'] ?? 0,
                    'is_representative' => isset($guestData['is_representative']) ? (int) $guestData['is_representative'] : ($index === 0 ? 1 : 0),
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
     *
     * @param \App\Models\Guest $guest
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleGuestStatus(Guest $guest): \Illuminate\Http\JsonResponse
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
     *
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCheckInData(Booking $booking): \Illuminate\Http\JsonResponse
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

                BookingGuest::create([
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

            // Lấy và xử lý danh sách khách
            $result = $this->processGuestAssignmentData($request, $booking);
            if ($result instanceof \Illuminate\Http\RedirectResponse) {
                return $result;
            }

            [$validGuests, $guestsByRoom, $roomDetails] = $result;

            // Thực hiện check-in trong transaction
            return $this->executeCheckInTransaction($booking, $validGuests, $guestsByRoom, $roomDetails);

        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Xử lý dữ liệu khách và phân nhóm theo phòng
     * @return array|\Illuminate\Http\RedirectResponse
     */
    private function processGuestAssignmentData(Request $request, Booking $booking)
    {
        $requestGuests = $request->input('guests', []);
        $validGuests = [];
        $guestsByRoom = [];
        $roomDetails = [];

        foreach ($requestGuests as $guest) {
            if (empty($guest['name'])) {
                continue;
            }

            $roomId = $guest['room_id'] ?? null;
            if (!$roomId) {
                return back()->with('error', "Khách {$guest['name']} chưa chọn phòng")->withInput();
            }

            $room = Room::with('roomType')->find($roomId);
            if (!$room) {
                return back()->with('error', "Phòng không tồn tại");
            }

            $validGuests[] = $guest;
            $roomDetails[$roomId] = $room;

            if (!isset($guestsByRoom[$roomId])) {
                $guestsByRoom[$roomId] = ['adults' => 0, 'children' => 0];
            }
            if (($guest['type'] ?? 'adult') === 'adult') {
                $guestsByRoom[$roomId]['adults']++;
            } else {
                $guestsByRoom[$roomId]['children']++;
            }
        }

        if (empty($validGuests)) {
            return back()->with('error', 'Vui lòng thêm ít nhất 1 khách')->withInput();
        }

        // Kiểm tra capacity
        $capacityError = $this->validateRoomCapacitiesForCheckin($guestsByRoom, $roomDetails);
        if ($capacityError) {
            return $capacityError;
        }

        return [$validGuests, $guestsByRoom, $roomDetails];
    }

    /**
     * Kiểm tra capacity phòng
     * @return \Illuminate\Http\RedirectResponse|null
     */
    private function validateRoomCapacitiesForCheckin(array $guestsByRoom, array $roomDetails)
    {
        foreach ($guestsByRoom as $roomId => $counts) {
            $room = $roomDetails[$roomId];
            $maxAdults = $room->roomType?->max_adults ?? 2;
            $maxChildren = $room->roomType?->max_children ?? 1;

            if ($counts['adults'] > $maxAdults) {
                return back()->with('error', "Phòng {$room->room_number} chỉ chứa tối đa {$maxAdults} người lớn, bạn đang có {$counts['adults']}")->withInput();
            }
            if ($counts['children'] > $maxChildren) {
                return back()->with('error', "Phòng {$room->room_number} chỉ chứa tối đa {$maxChildren} trẻ em, bạn đang có {$counts['children']}")->withInput();
            }
        }
        return null;
    }

    /**
     * Thực hiện check-in trong transaction
     */
    private function executeCheckInTransaction(Booking $booking, array $validGuests, array $guestsByRoom, array $roomDetails): \Illuminate\Http\RedirectResponse
    {
        DB::beginTransaction();
        try {
            $bookingRooms = $this->assignRoomsToBookingRoomsForCheckin($booking, $guestsByRoom);
            $this->saveGuestsForCheckin($booking, $validGuests, $bookingRooms);
            $result = $this->finalizeCheckInWithAssignment($booking, $guestsByRoom, $roomDetails);
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Gán phòng cho booking rooms
     */
    private function assignRoomsToBookingRoomsForCheckin(Booking $booking, array $guestsByRoom)
    {
        $selectedRoomIds = array_keys($guestsByRoom);
        $bookingRooms = $booking->bookingRooms;

        foreach ($bookingRooms as $index => $bookingRoom) {
            $roomId = $selectedRoomIds[$index] ?? $selectedRoomIds[0] ?? null;
            if ($roomId) {
                $bookingRoom->room_id = $roomId;
                $bookingRoom->save();
            }
        }

        return $booking->bookingRooms()->get();
    }

    /**
     * Lưu thông tin khách
     */
    private function saveGuestsForCheckin(Booking $booking, array $validGuests, $bookingRooms): void
    {
        $existingGuestIds = $booking->guests()->pluck('id')->toArray();
        $processedGuestIds = [];

        foreach ($validGuests as $index => $guestData) {
            $roomId = $guestData['room_id'];
            $bookingRoom = $bookingRooms->firstWhere('room_id', $roomId);
            $bookingRoomId = $bookingRoom?->id ?? $bookingRooms->first()?->id;

            // Lưu vào bảng guests (legacy)
            $guestId = $this->saveLegacyGuest($booking, $guestData, $roomId, $index, $existingGuestIds);
            if ($guestId) {
                $processedGuestIds[] = $guestId;
            }

            // Lưu vào bảng booking_guests (mới)
            $this->saveBookingGuest($booking, $guestData, $bookingRoomId);
        }

        // Xóa khách không còn trong danh sách
        $guestsToDelete = array_diff($existingGuestIds, $processedGuestIds);
        if (!empty($guestsToDelete)) {
            Guest::whereIn('id', $guestsToDelete)->delete();
        }
    }

    /**
     * Lưu khách vào bảng guests (legacy)
     */
    private function saveLegacyGuest(Booking $booking, array $guestData, int $roomId, int $index, array $existingGuestIds): ?int
    {
        if (isset($guestData['id']) && in_array($guestData['id'], $existingGuestIds)) {
            $guest = Guest::find($guestData['id']);
            if ($guest && $guest->booking_id == $booking->id) {
                $guest->update([
                    'name' => $guestData['name'],
                    'cccd' => $guestData['cccd'] ?? null,
                    'type' => $guestData['type'] ?? 'adult',
                    'room_id' => $roomId,
                    'checkin_status' => 'checked_in',
                ]);
                return $guest->id;
            }
        } else {
            $guest = Guest::create([
                'booking_id' => $booking->id,
                'room_id' => $roomId,
                'name' => $guestData['name'],
                'cccd' => $guestData['cccd'] ?? null,
                'type' => $guestData['type'] ?? 'adult',
                'checkin_status' => 'checked_in',
                'is_representative' => $index === 0 ? 1 : 0,
            ]);
            return $guest->id;
        }
        return null;
    }

    /**
     * Lưu khách vào bảng booking_guests
     */
    private function saveBookingGuest(Booking $booking, array $guestData, ?int $bookingRoomId): void
    {
        try {
            $bgGuest = BookingGuest::where('booking_id', $booking->id)
                ->where('name', $guestData['name'])
                ->first();

            $data = [
                'cccd' => $guestData['cccd'] ?? null,
                'type' => $guestData['type'] ?? 'adult',
                'status' => 'checked_in',
                'checkin_status' => 'checked_in',
                'booking_room_id' => $bookingRoomId,
            ];

            if ($bgGuest) {
                $bgGuest->update($data);
            } else {
                BookingGuest::create(array_merge($data, [
                    'booking_id' => $booking->id,
                    'booking_room_id' => $bookingRoomId,
                    'name' => $guestData['name'],
                    'is_representative' => 0,
                ]));
            }
        } catch (\Exception $e) {
            Log::error('Failed to save booking_guest', ['error' => $e->getMessage(), 'guest' => $guestData['name']]);
        }
    }

    /**
     * Hoàn tất check-in và tạo log
     */
    private function finalizeCheckInWithAssignment(Booking $booking, array $guestsByRoom, array $roomDetails): \Illuminate\Http\RedirectResponse
    {
        $old = $booking->status;
        $booking->status = 'checked_in';
        $booking->actual_check_in = Carbon::now();
        $booking->save();

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $staffName = $user?->full_name ?? 'Lễ tân';
        $rooms = $booking->bookingRooms()->with('room')->get()->map(fn($br) => $br->room?->name)->filter()->implode(', ');
        $roomText = $rooms ? " phòng {$rooms}" : '';

        BookingLog::create([
            'booking_id' => $booking->id,
            'user_id' => Auth::id(),
            'old_status' => $old,
            'new_status' => 'checked_in',
            'notes' => "{$staffName} check-in{$roomText}.",
            'changed_at' => now(),
        ]);

        Cache::forget("guest_info_{$booking->id}");

        $roomNumbers = collect($roomDetails)->pluck('room_number')->implode(', ');
        $totalAdults = collect($guestsByRoom)->sum('adults');
        $totalChildren = collect($guestsByRoom)->sum('children');

        return back()->with('success', "Check-in thành công! {$totalAdults} người lớn, {$totalChildren} trẻ em - Phòng: {$roomNumbers}");
    }

    /**
     * Cập nhật thông tin người đại diện
     */
    public function updateRepresentative(Request $request, Booking $booking)
    {
        try {
            $validated = $request->validate([
                'representative_name' => 'required|string|max:150',
                'cccd' => 'nullable|string|max:20',
            ]);

            $booking->update([
                'representative_name' => $validated['representative_name'],
                'cccd' => $validated['cccd'] ?? null,
            ]);

            return back()->with('success', 'Đã cập nhật thông tin người đại diện');
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
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

    /**
     * Xóa dịch vụ đã gán cho booking
     */
    public function deleteBookingService(BookingServiceRow $bookingService)
    {
        try {
            $booking = Booking::find($bookingService->booking_id);

            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            if (!$booking || !$user) {
                return back()->with('error', 'Không có quyền xóa dịch vụ.');
            }

            $roles = $user->roles();
            if (!$roles->whereIn('name', ['admin', 'staff'])->exists()) {
                return back()->with('error', 'Không có quyền xóa dịch vụ.');
            }

            if ($booking->status === 'cancelled' || !is_null($booking->actual_check_out)) {
                return back()->with('error', 'Không thể xóa dịch vụ của đơn đã hủy hoặc đã checkout.');
            }

            DB::beginTransaction();

            $lineTotal = (float) $bookingService->price * (int) $bookingService->quantity;
            $bookingService->delete();

            $booking->total_price = max(0.0, (float) $booking->total_price - $lineTotal);
            $booking->save();

            $payment = $booking->payments()->orderByDesc('id')->first();
            if ($payment && $payment->status === 'pending') {
                $payment->amount = max(0.0, (float) $payment->amount - $lineTotal);
                $payment->save();
            }

            $invoiceNote = $this->syncInvoiceExtrasIfExists($booking);

            DB::commit();

            return back()->with('success', 'Đã xóa dịch vụ.' . $invoiceNote);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('deleteBookingService failed', [
                'booking_service_id' => $bookingService->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Có lỗi khi xóa dịch vụ: ' . $e->getMessage());
        }
    }

    /**
     * Thay đổi phòng cho khách hàng
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changeRoom(Request $request, Booking $booking): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'old_room_id' => 'required|exists:rooms,id',
            'new_room_id' => 'required|exists:rooms,id|different:old_room_id',
            'reason'      => 'nullable|string|max:500',
        ]);

        return DB::transaction(fn () => $this->executeChangeRoomTransaction($validated, $booking));
    }

    /**
     * Execute the room change transaction logic
     *
     * @param array{old_room_id: int, new_room_id: int, reason?: string} $validated
     * @param \App\Models\Booking $booking
     * @return \Illuminate\Http\RedirectResponse
     */
    private function executeChangeRoomTransaction(array $validated, Booking $booking): \Illuminate\Http\RedirectResponse
    {
        $newRoom = Room::findOrFail($validated['new_room_id']);
        $oldRoomId = $validated['old_room_id'];

        // 1. Kiểm tra phòng mới có trống trong khoảng thời gian đó không
        $isOccupied = $this->isRoomOccupied($newRoom->id, $booking->id, $booking->check_in, $booking->check_out);
        if ($isOccupied) {
            return back()->with('error', 'Phòng mới đã có người đặt trong thời gian này!');
        }

        // 2. Cập nhật bảng booking_rooms
        $bookingRoom = \App\Models\BookingRoom::where('booking_id', $booking->id)
            ->where('room_id', $oldRoomId)
            ->first();

        if (!$bookingRoom) {
            return back()->with('error', 'Không tìm thấy thông tin phòng cũ trong đơn đặt phòng này.');
        }

        // 3. Tính toán giá phòng mới
        $checkIn = new \Carbon\Carbon($booking->check_in);
        $checkOut = new \Carbon\Carbon($booking->check_out);
        $nights = $bookingRoom->nights ?: $checkIn->diffInDays($checkOut) ?: 1;

        $priceData = $this->calculateNewRoomPrice($newRoom, $bookingRoom, $checkIn, $checkOut);

        $bookingRoom->update([
            'room_id' => $newRoom->id,
            'price_per_night' => $priceData['avg_price_per_night'],
            'subtotal' => $priceData['subtotal_new_room']
        ]);

        // 4. Xử lý bảng room_booked_dates
        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        $this->updateRoomBookedDates($booking->id, $oldRoomId, $newRoom->id, $period);

        // 5. Cập nhật trạng thái bảng rooms
        $this->updateRoomStatuses($oldRoomId, $newRoom->id, $booking->check_in, $booking->check_out);

        // 6. Tính lại Total Price của cả đơn đặt phòng
        $this->recalculateBookingTotalPrice($booking);

        // 7. Ghi lịch sử đổi phòng
        \App\Models\RoomChangeHistory::create([
            'booking_id' => $booking->id,
            'from_room_id' => $oldRoomId,
            'to_room_id' => $newRoom->id,
            'reason' => $validated['reason'] ?? 'Khách yêu cầu đổi phòng',
            'changed_by' => Auth::id(),
            'changed_at' => now(),
        ]);

        // 8. Cập nhật lại thanh toán nếu thiếu tiền hoặc thừa tiền
        $this->updatePaymentAmount($booking);

        return back()->with('success', 'Đổi phòng thành công! Số dư phòng cũ đã được ghi nhận.');
    }

    /**
     * Check if room is occupied during the booking period
     *
     * @param int $roomId
     * @param int $bookingId
     * @param string $checkIn
     * @param string $checkOut
     * @return bool
     */
    private function isRoomOccupied(int $roomId, int $bookingId, string $checkIn, string $checkOut): bool
    {
        return RoomBookedDate::where('room_id', $roomId)
            ->where('booking_id', '!=', $bookingId)
            ->whereBetween('booked_date', [$checkIn, \Carbon\Carbon::parse($checkOut)->subDay()->toDateString()])
            ->exists();
    }

    /**
     * Calculate new room price with occupancy surcharge
     *
     * @param \App\Models\Room $newRoom
     * @param \App\Models\BookingRoom $bookingRoom
     * @param \Carbon\Carbon $checkIn
     * @param \Carbon\Carbon $checkOut
     * @return array{avg_price_per_night: float, subtotal_new_room: float}
     */
    private function calculateNewRoomPrice(Room $newRoom, \App\Models\BookingRoom $bookingRoom, \Carbon\Carbon $checkIn, \Carbon\Carbon $checkOut): array
    {
        $bookingRoomPrices = [];
        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        $prices = RoomPrice::where('room_id', $newRoom->id)->get();
        $roomType = $newRoom->roomType;
        $subtotalNewRoom = 0;

        foreach ($period as $date) {
            $basePrice = $newRoom->catalogueBasePrice();
            foreach ($prices as $price) {
                if ($date->betweenIncluded($price->start_date, $price->end_date)) {
                    $basePrice = $price->price;
                    break;
                }
            }

            $adults = $bookingRoom->adults;
            $children611 = $bookingRoom->children_6_11;
            $children05 = $bookingRoom->children_0_5;

            RoomOccupancyPricing::validate($adults, $children611, $children05, $roomType);
            $breakdown = RoomOccupancyPricing::breakdown($basePrice, $adults, $children611, $children05, $roomType);
            $subtotalNewRoom += $breakdown['price_per_night'];
            $bookingRoomPrices[] = $breakdown['price_per_night'];
        }

        $avgPricePerNight = count($bookingRoomPrices) > 0
            ? (array_sum($bookingRoomPrices) / count($bookingRoomPrices))
            : $newRoom->catalogueBasePrice();

        return [
            'avg_price_per_night' => $avgPricePerNight,
            'subtotal_new_room' => $subtotalNewRoom
        ];
    }

    /**
     * Update room booked dates for room change
     *
     * @param int $bookingId
     * @param int $oldRoomId
     * @param int $newRoomId
     * @param CarbonPeriod $period
     * @return void
     */
    private function updateRoomBookedDates(int $bookingId, int $oldRoomId, int $newRoomId, CarbonPeriod $period): void
    {
        RoomBookedDate::where('booking_id', $bookingId)
            ->where('room_id', $oldRoomId)
            ->delete();

        $days = [];
        foreach ($period as $date) {
            $days[] = [
                'room_id' => $newRoomId,
                'booking_id' => $bookingId,
                'booked_date' => $date->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        RoomBookedDate::insert($days);
    }

    /**
     * Update room statuses after room change
     *
     * @param int $oldRoomId
     * @param int $newRoomId
     * @param string $checkIn
     * @param string $checkOut
     * @return void
     */
    private function updateRoomStatuses(int $oldRoomId, int $newRoomId, string $checkIn, string $checkOut): void
    {
        $today = now()->toDateString();
        if ($today >= $checkIn && $today < $checkOut) {
            Room::where('id', $oldRoomId)->update(['status' => 'maintenance']);
            Room::where('id', $newRoomId)->update(['status' => 'occupied']);
        }
    }

    /**
     * Recalculate booking total price including services and surcharges
     *
     * @param \App\Models\Booking $booking
     * @return void
     */
    private function recalculateBookingTotalPrice(Booking $booking): void
    {
        $newTotalPrice = $booking->bookingRooms()->sum('subtotal');
        $servicesTotal = $this->calculateBookingServicesTotal($booking);
        $surchargesTotal = $booking->surcharges()->sum('amount');

        $booking->update([
            'total_price' => $newTotalPrice + $servicesTotal + $surchargesTotal
        ]);
    }

    /**
     * Calculate total for booking services
     *
     * @param \App\Models\Booking $booking
     * @return float
     */
    private function calculateBookingServicesTotal(Booking $booking): float
    {
        return (float) $booking->bookingServices()->get()->sum(function ($bs) {
            return $bs->quantity * $bs->price;
        });
    }

    /**
     * Update payment amount to match new booking total
     *
     * @param \App\Models\Booking $booking
     * @return void
     */
    private function updatePaymentAmount(Booking $booking): void
    {
        $payment = Payment::where('booking_id', $booking->id)->orderByDesc('id')->first();
        if ($payment && in_array($payment->status, ['paid', 'partial'], true)) {
            $payment->update([
                'amount' => $booking->total_price
            ]);
        }
    }

    /**
     * Thay đổi phòng cho khách hàng - Sử dụng RoomChangeService (NEW)
     */
    public function changeRoomV2(RoomChangeRequest $request, Booking $booking, RoomChangeService $roomChangeService)
    {
        try {
            $result = $roomChangeService->changeRoom(
                $booking,
                (int) $request->old_room_id,
                (int) $request->new_room_id,
                $request->reason,
                Auth::id()
            );

            $message = 'Đổi phòng thành công!';
            if ($result['price_difference'] > 0) {
                $message .= ' Giá tăng thêm: ' . number_format($result['price_difference'], 0, ',', '.') . ' ₫';
            } elseif ($result['price_difference'] < 0) {
                $message .= ' Giá giảm: ' . number_format(abs($result['price_difference']), 0, ',', '.') . ' ₫';
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Room change failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Đổi phòng thất bại: ' . $e->getMessage());
        }
    }

    /**
     * Lấy danh sách phòng có thể đổi (API)
     */
    public function getAvailableRoomsForChange(Request $request, Booking $booking, RoomChangeService $roomChangeService)
    {
        $request->validate([
            'current_room_id' => 'required|integer|exists:rooms,id',
        ]);

        $rooms = $roomChangeService->getAvailableRoomsForChange(
            $booking,
            (int) $request->current_room_id
        );

        return response()->json([
            'success' => true,
            'data' => $rooms,
        ]);
    }

    /**
     * Lấy lịch sử đổi phòng của booking (API)
     */
    public function getRoomChangeHistory(Booking $booking, RoomChangeService $roomChangeService)
    {
        $history = $roomChangeService->getChangeHistory($booking->id);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
