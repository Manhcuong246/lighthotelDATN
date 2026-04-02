<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\RefundLog;
use App\Models\Room;
use App\Models\RoomBookedDate;
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

    public function store(StoreBookingRequest $request)
    {
        if (Auth::check() && Auth::user()?->canAccessAdmin()) {
            return back()->withErrors('Tài khoản nhân viên/quản trị không thể đặt phòng trên giao diện khách.')->withInput();
        }

        try {
            $booking = $this->bookingService->createBooking($request->validated());

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

            // Tự động đăng nhập cho khách mới (nếu chưa login)
            if (! Auth::check() && $booking->user) {
                Auth::login($booking->user);
            }

            return redirect()->away($paymentUrl);

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
     * Show cancellation confirmation page with refund preview.
     *
     * @param Booking $booking
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showCancelConfirmation(Booking $booking)
    {
        // Kiểm tra quyền sở hữu booking
        if ($booking->user_id !== Auth::id()) {
            return redirect()->route('bookings.index')
                ->withErrors('Bạn không có quyền hủy đơn đặt phòng này.');
        }

        try {
            $preview = $this->bookingService->previewCancellation($booking->id);

            return view('bookings.cancel-confirm', [
                'booking' => $booking,
                'preview' => $preview,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('bookings.show', $booking)
                ->withErrors($e->getMessage());
        }
    }

    /**
     * Cancel booking with refund calculation.
     *
     * @param Request $request
     * @param Booking $booking
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel(Request $request, Booking $booking)
    {
        // Kiểm tra quyền sở hữu booking
        if ($booking->user_id !== Auth::id()) {
            return back()->withErrors('Bạn không có quyền hủy đơn đặt phòng này.');
        }

        // Validate lý do hủy (tùy chọn)
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $reason = $request->input('reason');
            $result = $this->bookingService->cancelBooking($booking->id, $reason);

            // Xóa các ngày đã đặt
            RoomBookedDate::where('booking_id', $booking->id)->delete();

            // Build success message
            $message = 'Đơn đặt phòng đã được hủy thành công.';
            if ($result['refund_amount'] > 0) {
                $message .= ' ' . $result['message'];
            } else {
                $message .= ' Không có khoản hoàn tiền nào.';
            }

            return redirect()->route('bookings.show', $booking)
                ->with('success', $message)
                ->with('refund_details', $result);

        } catch (\Exception $e) {
            return back()->withErrors('Có lỗi xảy ra khi hủy đơn: ' . $e->getMessage());
        }
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
            return redirect()->route('bookings.index')
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



