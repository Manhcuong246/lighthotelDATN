<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\RoomBookedDate;
use App\Services\VnPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $payment = Payment::where('booking_id', $booking->id)
            ->where('method', 'vnpay')
            ->where('status', 'pending')
            ->first();

        // payment_method phải khớp vnpay nếu đã lưu; null = đơn cũ trước khi có cột/fillable
        $methodMismatch = $booking->payment_method !== null && $booking->payment_method !== 'vnpay';
        if (! $payment || $methodMismatch) {
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
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'vnp_')) {
                $inputData[$key] = $value;
            }
        }

        if (empty($inputData['vnp_TxnRef'])) {
            return redirect()->route('home')->withErrors('Thông tin thanh toán không hợp lệ.');
        }

        if (! $this->vnPayService->verifyReturn($inputData)) {
            return redirect()->route('home')->withErrors('Chữ ký không hợp lệ. Giao dịch có thể bị can thiệp.');
        }

        $vnpResponseCode = $inputData['vnp_ResponseCode'] ?? '';
        $vnpTxnRef = $inputData['vnp_TxnRef'];
        $vnpTransactionNo = $inputData['vnp_TransactionNo'] ?? null;
        $vnpAmount = isset($inputData['vnp_Amount']) ? (int) $inputData['vnp_Amount'] / 100 : 0;

        $bookingId = (int) str_replace('LIGHT', '', $vnpTxnRef);

        return DB::transaction(function () use ($bookingId, $vnpResponseCode, $vnpTransactionNo, $vnpAmount) {
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
                return redirect()->to(
                    URL::temporarySignedRoute('payment.success', now()->addHour(), ['booking' => $booking->id])
                )->with('success', 'Đơn hàng đã được xác nhận thanh toán trước đó.');
            }

            if ($vnpResponseCode === '00') {
                if (abs($payment->amount - $vnpAmount) > 1) {
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
                    return redirect()->to(
                        URL::temporarySignedRoute('payment.success', now()->addHour(), ['booking' => $booking->id])
                    )->with('success', 'Đơn hàng đã được xác nhận thanh toán trước đó.');
                }

                $booking->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                ]);

                return redirect()->to(
                    URL::temporarySignedRoute('payment.success', now()->addHour(), ['booking' => $booking->id])
                )->with('success', 'Thanh toán thành công! Đơn đặt phòng đã được xác nhận.');
            }

            // User hủy thanh toán hoặc giao dịch thất bại
            $payment->update(['status' => 'failed']);
            if ($booking->status === 'pending') {
                $booking->update(['status' => 'cancelled']);
                RoomBookedDate::where('booking_id', $booking->id)->delete();
            }

            return redirect()->route('payment.failed')
                ->with('error', 'Thanh toán không thành công. Mã lỗi: '.$vnpResponseCode.'. Vui lòng thử lại hoặc chọn phương thức khác.');
        });
    }
}
