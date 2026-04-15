<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\RefundLog;
use App\Models\Room;
use App\Models\RoomPrice;
use App\Models\User;
use App\Models\Service;
use App\Models\BookingService as BookingServiceModel;
use App\Services\VnPayService;
use App\Services\BookingService;
use App\Http\Requests\StoreBookingRequest;
use App\Exceptions\BookingException;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        // Debug: Log request data
        \Log::info('Booking request data:', $request->all());
        
        if (Auth::check() && Auth::user()?->canAccessAdmin()) {
            return back()->withErrors('Tài khoản nhân viên/quản trị không thể đặt phòng trên giao diện khách.')->withInput();
        }

        try {
            $validated = $request->validated();
            \Log::info('Validated data:', $validated);
            $booking = $this->bookingService->createBooking($validated);
            
            // Process guest information for admin bookings
            \Log::info('Processing guest data for booking ' . $booking->id, [
                'guest1_name' => $request->get('guest1_name'),
                'guest1_cccd' => $request->get('guest1_cccd'),
                'guest2_name' => $request->get('guest2_name'),
                'guest2_cccd' => $request->get('guest2_cccd')
            ]);
            
            // Create guest 1 if provided
            if (!empty($request->get('guest1_name'))) {
                \App\Models\BookingGuest::create([
                    'booking_id' => $booking->id,
                    'name' => $request->get('guest1_name'),
                    'cccd' => $request->get('guest1_cccd'),
                    'type' => 'adult',
                    'status' => 'pending',
                ]);
                \Log::info('Created guest 1 for booking ' . $booking->id);
            }
            
            // Create guest 2 if provided
            if (!empty($request->get('guest2_name'))) {
                \App\Models\BookingGuest::create([
                    'booking_id' => $booking->id,
                    'name' => $request->get('guest2_name'),
                    'cccd' => $request->get('guest2_cccd'),
                    'type' => 'adult',
                    'status' => 'pending',
                ]);
                \Log::info('Created guest 2 for booking ' . $booking->id);
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

            return $vnPayService->redirectAwayNoCache($paymentUrl);

        } catch (BookingException $e) {
            return back()->withErrors($e->getMessage())->withInput();
        } catch (\Exception $e) {
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
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
        $booking->update(['actual_check_in' => now()]);
        return back()->with('success', 'Check-in thành công');
    }

    public function checkOut(Booking $booking)
    {
        abort_unless($booking->isCheckoutAllowed(), 403);
        $booking->update([
            'actual_check_out' => now(),
            'status' => 'completed',
        ]);
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
}



