<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\RoomBookedDate;
use App\Services\VnPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VnPayController extends Controller
{
    public function __construct(
        protected VnPayService $vnPayService
    ) {}

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
        $booking = Booking::find($bookingId);

        if (! $booking) {
            return redirect()->route('home')->withErrors('Không tìm thấy đơn đặt phòng.');
        }

        $payment = Payment::where('booking_id', $booking->id)->where('method', 'vnpay')->first();

        if (! $payment) {
            return redirect()->route('home')->withErrors('Không tìm thấy thông tin thanh toán.');
        }

        if ($payment->status === 'paid') {
            return redirect()->route('payment.success', ['booking' => $booking->id])
                ->with('success', 'Đơn hàng đã được xác nhận thanh toán trước đó.');
        }

        if ($vnpResponseCode === '00') {
            if (abs($payment->amount - $vnpAmount) > 1) {
                return redirect()->route('home')->withErrors('Số tiền thanh toán không khớp.');
            }

            DB::beginTransaction();
            try {
                $payment->update([
                    'status' => 'paid',
                    'transaction_id' => $vnpTransactionNo,
                    'paid_at' => now(),
                ]);

                $booking->update(['status' => 'confirmed']);

                DB::commit();

                return redirect()->route('payment.success', ['booking' => $booking->id])
                    ->with('success', 'Thanh toán thành công! Đơn đặt phòng đã được xác nhận.');
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->route('home')->withErrors('Có lỗi xảy ra khi cập nhật đơn hàng.');
            }
        }

        // User hủy thanh toán hoặc giao dịch thất bại: cập nhật Payment + Booking + giải phóng ngày phòng
        DB::beginTransaction();
        try {
            $payment->update(['status' => 'failed']);
            if ($booking->status === 'pending') {
                $booking->update(['status' => 'cancelled']);
                RoomBookedDate::where('booking_id', $booking->id)->delete();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }

        return redirect()->route('payment.failed')
            ->with('error', 'Thanh toán không thành công. Mã lỗi: ' . $vnpResponseCode . '. Vui lòng thử lại hoặc chọn phương thức khác.');
    }
}
