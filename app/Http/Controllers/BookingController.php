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
use App\Models\HotelInfo;
use App\Services\VnPayService;
use App\Services\BookingService;
use App\Http\Requests\StoreBookingRequest;
use App\Exceptions\BookingException;
use App\Mail\PaymentInstructionMail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
        if (Auth::check() && Auth::user()?->canAccessAdmin()) {
            return back()->withErrors('Tài khoản nhân viên/quản trị không thể đặt phòng trên giao diện khách.')->withInput();
        }

        try {
            $booking = $this->bookingService->createBooking($request->validated());

            // Gửi email xác nhận booking ngay sau khi tạo
            try {
                $hotelInfo = HotelInfo::first();
                $nights = $booking->check_out->diffInDays($booking->check_in);
                
                Mail::to($booking->user->email)->send(new PaymentInstructionMail(
                    $booking,
                    $hotelInfo,
                    $nights,
                    null,
                    null
                ));
            } catch (\Exception $e) {
                \Log::warning('Không gửi được email xác nhận booking: ' . $e->getMessage());
                // Không dừng flow, tiếp tục redirect VNPay
            }

            // Redirect VNPay
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



