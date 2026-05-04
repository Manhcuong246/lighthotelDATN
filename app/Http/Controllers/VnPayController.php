<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingException;
use App\Mail\VnPayPaidPortalMail;
use App\Models\Booking;
use App\Models\BookingPaymentIntent;
use App\Models\HotelInfo;
use App\Models\Payment;
use App\Services\BookingService;
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
        $vnpTxnRef = (string) $inputData['vnp_TxnRef'];
        $vnpTransactionNo = $inputData['vnp_TransactionNo'] ?? null;
        $vnpAmountVnd = isset($inputData['vnp_Amount']) ? (int) ((int) $inputData['vnp_Amount'] / 100) : 0;
        $vnpAmountRaw = $inputData['vnp_Amount'] ?? null;

        $intentId = null;
        $bookingId = null;
        if (str_starts_with($vnpTxnRef, BookingPaymentIntent::TXN_REF_PREFIX)) {
            $intentId = (int) substr($vnpTxnRef, strlen(BookingPaymentIntent::TXN_REF_PREFIX));
        } elseif (str_starts_with($vnpTxnRef, 'LIGHT')) {
            $bookingId = (int) substr($vnpTxnRef, strlen('LIGHT'));
        } else {
            return redirect()->route('home')->withErrors('Mã giao dịch thanh toán không hợp lệ.');
        }

        if (($intentId !== null && $intentId < 1) || ($bookingId !== null && $bookingId < 1)) {
            return redirect()->route('home')->withErrors('Mã giao dịch thanh toán không hợp lệ.');
        }

        /** @var BookingService $bookingService */
        $bookingService = app(BookingService::class);

        $sendPortalEmailForBookingId = null;

        $response = DB::transaction(function () use (
            $intentId,
            $bookingId,
            $vnpResponseCode,
            $vnpTransactionNo,
            $vnpAmountVnd,
            $vnpAmountRaw,
            &$sendPortalEmailForBookingId,
            $bookingService
        ) {
            if ($intentId !== null) {
                $intent = BookingPaymentIntent::query()->whereKey($intentId)->lockForUpdate()->first();

                if (! $intent) {
                    return redirect()->route('home')->withErrors('Không tìm thấy phiên đặt phòng. Vui lòng đặt lại.');
                }

                if ($intent->completed_at && $intent->booking_id) {
                    $prior = Booking::query()->whereKey((int) $intent->booking_id)->first();
                    if (! $prior) {
                        return redirect()->route('home')->withErrors('Không tìm thấy đơn đặt phòng.');
                    }

                    VnPaySuccessSession::grant($prior);

                    return redirect()->to($this->postPaymentBookingDetailPath($prior));
                }

                if ($intent->abandoned_at) {
                    return redirect()->route('home')->withErrors('Phiên đặt phòng không còn hiệu lực. Vui lòng đặt lại.');
                }

                if ($intent->expires_at && $intent->expires_at->isPast()) {
                    return redirect()->route('home')->withErrors('Phiên đặt phòng đã hết hạn. Vui lòng đặt và thanh toán lại trong thời gian cho phép.');
                }

                if ($vnpResponseCode !== '00') {
                    if (! $intent->completed_at) {
                        BookingPaymentIntent::whereKey($intent->id)->update(['abandoned_at' => now()]);
                    }

                    return redirect()->route('payment.failed')
                        ->with('error', 'Thanh toán không thành công. Mã lỗi: '.$vnpResponseCode.'. Vui lòng thử đặt lại.');
                }

                $expectedVnd = (int) $intent->amount_vnd;
                if ($expectedVnd !== $vnpAmountVnd) {
                    Log::warning('vnpay.intent.return_amount_mismatch', [
                        'intent_id' => $intent->id,
                        'intent_amount_vnd' => $expectedVnd,
                        'vnp_amount_vnd' => $vnpAmountVnd,
                        'vnp_Amount_raw' => $vnpAmountRaw,
                    ]);

                    return redirect()->route('home')->withErrors('Số tiền thanh toán không khớp.');
                }

                /** @var array<string, mixed> $payload */
                $payload = is_array($intent->payload) ? $intent->payload : [];

                try {
                    $booking = $bookingService->createBooking($payload, (string) $vnpTransactionNo, $expectedVnd);
                } catch (BookingException $e) {
                    Log::warning('vnpay.intent_finalize_booking_failed', [
                        'intent_id' => $intent->id,
                        'msg' => $e->getMessage(),
                    ]);

                    return redirect()->route('home')->withErrors($e->getMessage());
                }

                BookingPaymentIntent::whereKey($intent->id)->update([
                    'booking_id' => $booking->id,
                    'completed_at' => now(),
                ]);

                $sendPortalEmailForBookingId = $booking->id;

                $booking->refresh();
                VnPaySuccessSession::grant($booking);

                return redirect()->to($this->postPaymentBookingDetailPath($booking));
            }

            $booking = Booking::query()->whereKey((int) $bookingId)->lockForUpdate()->first();

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
                try {
                    $bookingService->materializeAdminPendingCheckoutFromPayload($booking);
                } catch (BookingException $e) {
                    Log::error('vnpay.admin_pending_checkout_materialize_failed', [
                        'booking_id' => $booking->id,
                        'message' => $e->getMessage(),
                    ]);
                }

                VnPaySuccessSession::grant($booking);

                return redirect()->to($this->postPaymentBookingDetailPath($booking));
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
                    try {
                        $bookingService->materializeAdminPendingCheckoutFromPayload($booking);
                    } catch (BookingException $e) {
                        Log::error('vnpay.admin_pending_checkout_materialize_failed', [
                            'booking_id' => $booking->id,
                            'message' => $e->getMessage(),
                        ]);
                    }

                    VnPaySuccessSession::grant($booking);

                    return redirect()->to($this->postPaymentBookingDetailPath($booking));
                }

                $booking->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                ]);

                try {
                    $bookingService->materializeAdminPendingCheckoutFromPayload($booking);
                } catch (BookingException $e) {
                    Log::error('vnpay.admin_pending_checkout_materialize_failed', [
                        'booking_id' => $booking->id,
                        'message' => $e->getMessage(),
                    ]);
                }

                $sendPortalEmailForBookingId = $booking->id;

                $booking->refresh();
                VnPaySuccessSession::grant($booking);

                return redirect()->to($this->postPaymentBookingDetailPath($booking));
            }

            // Đơn admin chờ link VNPay: cho phép thử lại; không có bản ghi đặt chỗ chờ trả tiền từ website.
            $payment->update(['status' => 'failed']);

            return redirect()->route('payment.failed')
                ->with('error', 'Thanh toán không thành công. Mã lỗi: '.$vnpResponseCode.'. Vui lòng thử lại hoặc chọn phương thức khác.');
        });

        if ($sendPortalEmailForBookingId !== null) {
            $mailBookingId = $sendPortalEmailForBookingId;

            dispatch(function () use ($mailBookingId): void {
                try {
                    $bookingForMail = Booking::query()
                        ->with(['user', 'payment', 'rooms.roomType', 'bookingRooms.roomType'])
                        ->find($mailBookingId);
                    $email = $bookingForMail?->user?->email;
                    $mailUser = $bookingForMail?->user;
                    if (! $bookingForMail || ! $mailUser || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        return;
                    }

                    $ttlDays = max(1, (int) config('booking.signed_booking_show_ttl_days', 90));
                    $expires = now()->addDays($ttlDays);
                    $bookingDetailUrl = URL::to(URL::temporarySignedRoute(
                        'bookings.show',
                        $expires,
                        ['booking' => $bookingForMail->id, 'portal_user' => $bookingForMail->user_id],
                        false
                    ));
                    $invoiceUrl = BookingInvoiceViewData::guestCanViewInvoiceSheet($bookingForMail)
                        ? URL::to(URL::temporarySignedRoute(
                            'bookings.invoice',
                            $expires,
                            ['booking' => $bookingForMail->id, 'portal_user' => $bookingForMail->user_id],
                            false
                        ))
                        : $bookingDetailUrl;
                    $indexUrl = URL::to(URL::temporarySignedRoute(
                        'guest.bookings.index',
                        $expires,
                        ['user' => $bookingForMail->user_id],
                        false
                    ));
                    Mail::to($email)->send(new VnPayPaidPortalMail(
                        $bookingForMail,
                        $bookingDetailUrl,
                        $invoiceUrl,
                        HotelInfo::first(),
                        $indexUrl
                    ));
                } catch (\Throwable $e) {
                    Log::warning('Failed to send post-VNPay paid confirmation email', [
                        'booking_id' => $mailBookingId,
                        'error' => $e->getMessage(),
                    ]);
                }
            })->afterResponse();
        }

        return $response;
    }

    private function postPaymentBookingDetailPath(Booking $booking): string
    {
        return URL::temporarySignedRoute(
            'bookings.show',
            now()->addDays(max(1, (int) config('booking.signed_booking_show_ttl_days', 90))),
            ['booking' => $booking->id, 'portal_user' => $booking->user_id],
            false
        );
    }
}
