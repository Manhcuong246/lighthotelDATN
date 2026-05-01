<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\RefundLog;
use App\Models\Room;
use App\Models\User;
use App\Models\BookingLog;
use App\Models\HotelInfo;
use App\Mail\PaymentInstructionMail;
use App\Services\VnPayService;
use App\Services\BookingService;
use App\Http\Requests\StoreBookingRequest;
use App\Exceptions\BookingException;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Carbon\CarbonPeriod;

class BookingController extends Controller
{
    protected $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Chi tiết đơn: chủ tài khoản đã đăng nhập, hoặc khách mở link có chữ ký (sau thanh toán / email).
     */
    public function show(Request $request, Booking $booking)
    {
        $booking->load(['user', 'rooms.roomType', 'payment', 'refundLogs']);

        $isOwner = Auth::check() && (int) Auth::id() === (int) $booking->user_id;
        if ($isOwner || $request->hasValidSignature()) {
            return view('bookings.show', compact('booking'));
        }

        abort(403, 'Bạn không có quyền xem đơn đặt phòng này. Đăng nhập đúng tài khoản đặt phòng hoặc dùng link được gửi trong email xác nhận.');
    }

    public function store(StoreBookingRequest $request)
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user && $user->canAccessAdmin()) {
            return back()->withErrors('Tài khoản nhân viên/quản trị không thể đặt phòng trên giao diện khách.')->withInput();
        }

        try {
            $validated = $request->validated();
            $booking = $this->bookingService->createBooking($validated);

            // Tạo 1 Guest duy nhất - người đại diện
            if ($request->filled('name') && $request->filled('cccd')) {
                Guest::create([
                    'booking_id'       => $booking->id,
                    'room_type'        => null,
                    'room_index'       => 0,
                    'name'             => trim($request->input('name')),
                    'cccd'             => trim($request->input('cccd')),
                    'type'             => 'adult',
                    'is_representative'=> 1,
                    'checkin_status'   => 'pending',
                ]);
            }

            // 11. Redirect VNPay
            $vnPayService = app(VnPayService::class);
            $returnUrl    = route('payment.vnpay.return');
            $orderInfo    = 'Dat phong Light Hotel #' . $booking->id;
            $txnRef       = 'LIGHT' . $booking->id;
            $amountVND    = (int) round($booking->total_price);
            $bankCode     = $request->input('bank_code') ?: null;

            $paymentUrl = $vnPayService->createPaymentUrl(
                $txnRef,
                $amountVND,
                $orderInfo,
                $returnUrl,
                $request->ip(),
                'vn',
                $bankCode
            );

            $this->sendCustomerVnPayInstructionMail($booking);

            return $vnPayService->redirectAwayNoCache($paymentUrl);

        } catch (BookingException $e) {
            return back()->withErrors($e->getMessage())->withInput();
        } catch (\Exception $e) {
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    private function sendCustomerVnPayInstructionMail(Booking $booking): void
    {
        try {
            $booking->loadMissing(['user', 'room.roomType', 'rooms.roomType', 'bookingRooms.room.roomType']);
            $nights = max(1, (int) $booking->check_in->diffInDays($booking->check_out));
            $payEntryDays = max(1, (int) config('vnpay.pay_entry_signed_ttl_days', 14));
            $vnpayPayUrl = URL::signedRoute(
                'payment.vnpay.pay',
                ['booking' => $booking->id],
                now()->addDays($payEntryDays)
            );

            Mail::to($booking->user->email)->send(new PaymentInstructionMail(
                $booking,
                HotelInfo::first(),
                $nights,
                null,
                $vnpayPayUrl
            ));
        } catch (\Throwable $e) {
            Log::warning('Failed to send VNPay payment email for customer booking', [
                'booking_id' => $booking->id,
                'user_email' => $booking->user->email ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function normalizeGuestPayload(array $guests): array
    {
        $flattened = [];

        foreach ($guests as $topKey => $value) {
            if (! is_array($value)) {
                continue;
            }

            if (isset($value['name']) || isset($value['cccd']) || isset($value['type']) || isset($value['room_index'])) {
                $flattened[] = [
                    'room_type' => null,
                    'room_index' => isset($value['room_index']) ? (int) $value['room_index'] : 0,
                    'name' => trim((string) ($value['name'] ?? '')),
                    'cccd' => trim((string) ($value['cccd'] ?? '')),
                    'type' => $value['type'] ?? 'adult',
                ];
                continue;
            }

            foreach ($value as $guestData) {
                if (! is_array($guestData)) {
                    continue;
                }

                $flattened[] = [
                    'room_type' => trim((string) $topKey),
                    'room_index' => 0,
                    'name' => trim((string) ($guestData['name'] ?? '')),
                    'cccd' => trim((string) ($guestData['cccd'] ?? '')),
                    'type' => $guestData['type'] ?? 'adult',
                ];
            }
        }

        return $flattened;
    }

    public function update(Request $request, Booking $booking)
    {
        // Giữ nguyên update nhưng xóa calculateTotalPrice ở dưới nếu trùng lặp
        $data = $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled',
        ]);
        $booking->update(['status' => $data['status']]);
        return back()->with('success', 'Cập nhật trạng thái thành công');
    }

    public function checkIn(Booking $booking)
    {
        abort_unless($booking->isCheckinAllowed(), 403);
        $booking->update([
            'status' => 'checked_in',
            'actual_check_in' => now(),
        ]);
        return back()->with('success', 'Check-in thành công');
    }

    public function checkOut(Booking $booking)
    {
        abort_unless($booking->isCheckoutAllowed(), 403);

        $oldStatus = $booking->status;
        $staffName = auth()->user()?->full_name ?? 'Hệ thống';

        $booking->update([
            'actual_check_out' => now(),
            'status' => 'completed',
        ]);

        if ($oldStatus !== 'completed') {
            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => 'completed',
                'notes' => "{$staffName} check-out.",
                'changed_at' => now(),
            ]);
        }

        return back()->with('success', 'Check-out thành công');
    }

    /**
     * Display refund details for a cancelled booking.
     *
     * @param Booking $booking
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showRefundDetails(Booking $booking)
    {
        if ($booking->user_id !== Auth::id()) {
            return redirect()->route('home')
                ->withErrors('Bạn không có quyền xem thông tin này.');
        }

        if ($booking->status !== 'cancelled') {
            return redirect()->route('bookings.show', $booking)
                ->withErrors('Đơn đặt phòng chưa bị hủy.');
        }

        $refundLog = RefundLog::where('booking_id', $booking->id)->first();

        return view('bookings.refund-details', [
            'booking' => $booking,
            'refundLog' => $refundLog,
        ]);
    }

    /**
     * Hiển thị form đặt phòng đơn giản
     */
    public function createSimple(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|integer|exists:rooms,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        $room = Room::with('roomType')->findOrFail($validated['room_id']);

        return view('bookings.create-simple', [
            'room' => $room,
            'checkIn' => $validated['check_in'],
            'checkOut' => $validated['check_out'],
        ]);
    }

    /**
     * Xử lý lưu đặt phòng đơn giản
     */
    public function storeSimple(\App\Http\Requests\StoreSimpleBookingRequest $request)
    {
        /** @var User|null $user */
        $user = Auth::user();

        try {
            $validated = $request->validated();

            DB::beginTransaction();

            // 1. Tạo booking - tính giá theo số phòng
            $nights = max(1, now()->parse($validated['check_in'])->diffInDays($validated['check_out']));
            $room = Room::with('roomType')->findOrFail($validated['room_id']);
            $roomsCount = $validated['rooms'];

            $booking = Booking::create([
                'user_id'     => $user?->id,
                'check_in'    => $validated['check_in'],
                'check_out'   => $validated['check_out'],
                'adults'      => $roomsCount, // Lưu số phòng vào adults hoặc thêm field rooms_count
                'total_price' => $room->base_price * $nights * $roomsCount, // Giá = giá phòng * số đêm * số phòng
                'status'      => 'pending',
            ]);

            // 2. Gắn phòng vào booking (gắn 1 phòng, số lượng được tính qua total_price)
            $booking->rooms()->attach($room->id, [
                'price_per_night' => $room->base_price,
                'nights'          => $nights,
            ]);

            // 3. Tạo 1 guest duy nhất - người đại diện
            Guest::create([
                'booking_id'       => $booking->id,
                'name'             => $validated['name'],
                'cccd'             => $validated['cccd'],
                'type'             => 'adult',
                'is_representative'=> 1,
                'checkin_status'   => 'pending',
            ]);

            DB::commit();

            return redirect()->route('bookings.show', $booking)
                ->with('success', 'Đặt phòng thành công! Vui lòng thanh toán để hoàn tất.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Simple booking error: ' . $e->getMessage());
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }
}



