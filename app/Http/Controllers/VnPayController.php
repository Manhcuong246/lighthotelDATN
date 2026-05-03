<?php

namespace App\Http\Controllers;

use App\Mail\VnPayPaidPortalMail;
use App\Models\Booking;
use App\Models\HotelInfo;
use App\Models\Payment;
use App\Support\BookingInvoiceViewData;
use App\Support\VnPaySuccessSession;
use App\Services\VnPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class VnPayController extends Controller
{
    public function __construct(
        protected VnPayService $vnPayService
    ) {}

    /**
     * Khách bấm link có chữ ký trong email → tại đây mới tạo URL VNPay (ExpireDate tính từ lúc bấm).
     */
    public function pay(Request $request, Booking $booking)
    {
        if ($booking->status !== 'pending') {
            return redirect()->route('home')
                ->withErrors('Liên kết thanh toán không còn hiệu lực cho đơn này.');
        }

        $payment = Payment::where('booking_id', $booking->id)
            ->where('method', 'vnpay')
            ->orderByDesc('id')
            ->first();

        // payment_method phải khớp vnpay nếu đã lưu; null = đơn cũ trước khi có cột/fillable
        $methodMismatch = $booking->payment_method !== null && $booking->payment_method !== 'vnpay';
        if (! $payment || $methodMismatch) {
            return redirect()->route('home')
                ->withErrors('Liên kết thanh toán không hợp lệ hoặc đơn đã được xử lý.');
        }

        if ($payment->status === 'failed') {
            $payment->update([
                'status' => 'pending',
                'transaction_id' => null,
                'paid_at' => null,
            ]);
        }

        if ($payment->status !== 'pending') {
            return redirect()->route('home')
                ->withErrors('Liên kết thanh toán không hợp lệ hoặc đơn đã được xử lý.');
        }

        $txnRef = 'LIGHT'.$booking->id;
        $amountVnd = (int) round((float) ($booking->total_price ?? 0));
        $orderInfo = 'Dat phong Light Hotel #'.$booking->id;
        $paymentUrl = $this->vnPayService->createPaymentUrl(
            $txnRef,
            $amountVnd,
            $orderInfo,
            route('payment.vnpay.return'),
            $request->ip() ?: config('vnpay.server_ip', '127.0.0.1'),
            'vn',
            null
        );

        return $this->vnPayService->redirectAwayNoCache($paymentUrl);
    }

    public function return(Request $request)
    {
        $inputData = [];
        foreach ($request->query() as $key => $value) {
            if (is_string($key) && str_starts_with($key, 'vnp_')) {
                $inputData[$key] = $value;
            }
        }

        if (empty($inputData['vnp_TxnRef'])) {
            return redirect()->route('home')->withErrors('Thông tin thanh toán không hợp lệ.');
        }

        if (! $this->vnPayService->verifyReturn($inputData)) {
            Log::warning('vnpay.return_signature_mismatch', [
                'vnp_TxnRef' => $inputData['vnp_TxnRef'] ?? null,
                'vnp_ResponseCode' => $inputData['vnp_ResponseCode'] ?? null,
                'query_keys' => array_keys($inputData),
            ]);

            return redirect()->route('home')->withErrors('Chữ ký không hợp lệ. Giao dịch có thể bị can thiệp.');
        }

        $vnpResponseCode = $inputData['vnp_ResponseCode'] ?? '';
        $vnpTxnRef = $inputData['vnp_TxnRef'];
        $vnpTransactionNo = $inputData['vnp_TransactionNo'] ?? null;
        $vnpAmountVnd = isset($inputData['vnp_Amount']) ? (int) ((int) $inputData['vnp_Amount'] / 100) : 0;
        $vnpAmountRaw = $inputData['vnp_Amount'] ?? null;

        $bookingId = (int) str_replace('LIGHT', '', $vnpTxnRef);

        $sendPortalEmailForBookingId = null;

        $response = DB::transaction(function () use ($bookingId, $vnpResponseCode, $vnpTransactionNo, $vnpAmountVnd, $vnpAmountRaw, &$sendPortalEmailForBookingId) {
            $booking = Booking::query()->whereKey($bookingId)->lockForUpdate()->first();

            if (! $booking) {
                return redirect()->route('home')->withErrors('Không tìm thấy đơn đặt phòng.');
            }

            $payment = Payment::query()
                ->where('booking_id', $booking->id)
                ->where('method', 'vnpay')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                return redirect()->route('home')->withErrors('Không tìm thấy thông tin thanh toán.');
            }

            if ($payment->status === 'paid') {
                VnPaySuccessSession::grant($booking);

                return redirect()->to(
                    URL::temporarySignedRoute('payment.success', now()->addHour(), ['booking' => $booking->id], false)
                )->with('success', 'Đơn hàng đã được xác nhận thanh toán trước đó.');
            }

            if ($vnpResponseCode === '00') {
                $expectedVnd = (int) round((float) $payment->amount);
                if ($expectedVnd !== $vnpAmountVnd) {
                    Log::warning('vnpay.return_amount_mismatch', [
                        'booking_id' => $booking->id,
                        'payment_amount_vnd' => $expectedVnd,
                        'vnp_amount_vnd' => $vnpAmountVnd,
                        'vnp_Amount_raw' => $vnpAmountRaw,
                    ]);

                    return redirect()->route('home')->withErrors('Số tiền thanh toán không khớp.');
                }

                $updated = Payment::query()
                    ->whereKey($payment->id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'paid',
                        'transaction_id' => $vnpTransactionNo,
                        'paid_at' => now(),
                    ]);

                if ($updated === 0) {
                    VnPaySuccessSession::grant($booking);

                    return redirect()->to(
                        URL::temporarySignedRoute('payment.success', now()->addHour(), ['booking' => $booking->id], false)
                    )->with('success', 'Đơn hàng đã được xác nhận thanh toán trước đó.');
                }

                $booking->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                ]);

                $sendPortalEmailForBookingId = $booking->id;

                $booking->refresh();
                VnPaySuccessSession::grant($booking);

                return redirect()->to(
                    URL::temporarySignedRoute('payment.success', now()->addHour(), ['booking' => $booking->id], false)
                )->with('success', 'Thanh toán thành công! Đơn đặt phòng đã được xác nhận.');
            }

            // Khách đóng cổng / giao dịch từ chối: giữ đơn chờ thanh toán để thử lại, không hủy đơn.
            $payment->update(['status' => 'failed']);

            return redirect()->route('payment.failed')
                ->with('error', 'Thanh toán không thành công. Mã lỗi: '.$vnpResponseCode.'. Vui lòng thử lại hoặc chọn phương thức khác.');
        });

        if ($sendPortalEmailForBookingId !== null) {
            try {
                $bookingForMail = Booking::query()
                    ->with(['user', 'payment', 'rooms.roomType', 'bookingRooms.roomType'])
                    ->find($sendPortalEmailForBookingId);
                $email = $bookingForMail?->user?->email;
                $mailUser = $bookingForMail?->user;
                if ($bookingForMail && $mailUser && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $ttlDays = max(1, (int) config('booking.signed_booking_show_ttl_days', 90));
                    $expires = now()->addDays($ttlDays);
                    $invoiceUrl = BookingInvoiceViewData::guestCanViewInvoiceSheet($bookingForMail)
                        ? URL::to(URL::temporarySignedRoute(
                            'bookings.invoice',
                            $expires,
                            ['booking' => $bookingForMail->id, 'portal_user' => $bookingForMail->user_id],
                            false
                        ))
                        : URL::to(URL::temporarySignedRoute(
                            'bookings.show',
                            $expires,
                            ['booking' => $bookingForMail->id, 'portal_user' => $bookingForMail->user_id],
                            false
                        ));
                    $indexUrl = URL::to(URL::temporarySignedRoute(
                        'guest.bookings.index',
                        $expires,
                        ['user' => $bookingForMail->user_id],
                        false
                    ));
                    Mail::to($email)->send(new VnPayPaidPortalMail(
                        $bookingForMail,
                        $invoiceUrl,
                        HotelInfo::first(),
                        $indexUrl
                    ));
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to send post-VNPay paid confirmation email', [
                    'booking_id' => $sendPortalEmailForBookingId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $response;
    }
}
