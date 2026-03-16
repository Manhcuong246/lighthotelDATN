<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\VNPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Hiển thị trang thanh toán deposit (30%)
     */
    public function showPaymentForm(Booking $booking)
    {
        // Chỉ cho phép thanh toán nếu status = awaiting_payment và chưa thanh toán
        if (!$booking->requiresDeposit()) {
            return redirect()->route('account.bookings')
                ->with('error', 'Đơn đặt phòng này không cần thanh toán.');
        }

        // Kiểm tra permission: chỉ user tạo booking hoặc admin mới được xem
        if (auth()->id() !== $booking->user_id && !auth()->user()->canAccessAdmin()) {
            abort(403);
        }

        $depositAmount = $booking->getDepositAmount();
        $remainingAmount = $booking->total_price - $depositAmount;

        return view('payments.deposit', compact('booking', 'depositAmount', 'remainingAmount'));
    }

    /**
     * Xử lý thanh toán deposit qua VNPay
     */
    public function processPayment(Request $request, Booking $booking)
    {
        // Validate
        $request->validate([
            'payment_method' => 'required|in:vnpay,momo,bank_transfer',
        ]);

        // Kiểm tra lại điều kiện thanh toán
        if (!$booking->requiresDeposit()) {
            return back()->withErrors('Đơn đặt phòng này không cần thanh toán.');
        }

        // Kiểm tra permission
        if (auth()->id() !== $booking->user_id) {
            abort(403);
        }

        // Nếu chọn VNPay
        if ($request->payment_method === 'vnpay') {
            return $this->processVNPayPayment($booking);
        }

        // Các phương thức khác (xử lý như cũ)
        return $this->processManualPayment($booking, $request->payment_method);
    }

    /**
     * Xử lý thanh toán qua VNPay
     */
    protected function processVNPayPayment(Booking $booking)
    {
        try {
            $vnpayService = new VNPayService();
            $amount = $booking->getDepositAmount();
            
            $paymentUrl = $vnpayService->createPaymentUrl($booking, $amount);
            
            // Lưu thông tin payment đang chờ
            $booking->update([
                'payment_request_sent_at' => now(),
                'payment_method' => 'vnpay',
            ]);

            // Redirect sang VNPay
            return redirect($paymentUrl);
        } catch (\Exception $e) {
            Log::error('VNPay payment failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors('Có lỗi xảy ra khi chuyển hướng đến VNPay. Vui lòng thử lại sau.');
        }
    }

    /**
     * Xử lý thanh toán thủ công (MoMo, Bank Transfer)
     */
    protected function processManualPayment(Booking $booking, string $paymentMethod)
    {
        // Giả lập xử lý payment thành công
        // Sau này sẽ tích hợp API thực tế của MoMo
        $transactionId = 'TXN' . time() . rand(1000, 9999);

        \DB::beginTransaction();
        try {
            // Update booking với thông tin thanh toán
            $booking->update([
                'deposit_paid_at' => now(),
                'payment_method' => $paymentMethod,
                'payment_transaction_id' => $transactionId,
                'status' => Booking::STATUS_CONFIRMED, // Chuyển sang confirmed sau khi thanh toán
            ]);

            // Tạo payment record
            \App\Models\Payment::create([
                'booking_id' => $booking->id,
                'user_id' => auth()->id(),
                'amount' => $booking->getDepositAmount(),
                'method' => $paymentMethod, // Giữ method để tương thích backward
                'payment_method' => $paymentMethod,
                'transaction_id' => $transactionId,
                'status' => 'success',
                'paid_at' => now(),
            ]);

            \DB::commit();

            Log::info("Thanh toán deposit thành công", [
                'booking_id' => $booking->id,
                'amount' => $booking->getDepositAmount(),
                'method' => $paymentMethod,
                'transaction_id' => $transactionId,
            ]);

            return redirect()->route('account.bookings')
                ->with('success', 'Thanh toán deposit thành công! Đơn đặt phòng của bạn đã được xác nhận.');
        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error("Thanh toán deposit thất bại", [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors('Có lỗi xảy ra trong quá trình thanh toán. Vui lòng thử lại sau.');
        }
    }

    /**
     * Payment callback từ VNPay
     */
    public function callback(Request $request)
    {
        Log::info('VNPay callback received', $request->all());
        
        $vnpayService = new VNPayService();
        
        // Xác thực response từ VNPay
        if (!$vnpayService->validateResponse($request->all())) {
            Log::error('VNPay callback validation failed');
            return redirect()->route('home')
                ->with('error', 'Giao dịch không hợp lệ.');
        }
        
        // Lấy thông tin từ response
        $responseCode = $request->get('vnp_ResponseCode');
        $transactionId = $request->get('vnp_TransactionNo');
        $bookingId = $request->get('vnp_TxnRef');
        $amount = $request->get('vnp_Amount') / 100; // Chia cho 100 để ra số tiền gốc
        
        \DB::beginTransaction();
        try {
            $booking = Booking::findOrFail($bookingId);
            
            // Kiểm tra response code (00 = thành công)
            if ($responseCode === '00') {
                // Thanh toán thành công
                $booking->update([
                    'deposit_paid_at' => now(),
                    'payment_method' => 'vnpay',
                    'payment_transaction_id' => $transactionId,
                    'status' => Booking::STATUS_CONFIRMED,
                ]);
                
                // Tạo payment record
                \App\Models\Payment::create([
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'amount' => $amount,
                    'payment_method' => 'vnpay',
                    'transaction_id' => $transactionId,
                    'status' => 'success',
                    'paid_at' => now(),
                ]);
                
                \DB::commit();
                
                Log::info('VNPay payment successful', [
                    'booking_id' => $booking->id,
                    'transaction_id' => $transactionId,
                    'amount' => $amount,
                ]);
                
                return redirect()->route('account.bookings')
                    ->with('success', 'Thanh toán thành công! Đơn đặt phòng đã được xác nhận.');
            } else {
                // Thanh toán thất bại
                \DB::rollBack();
                
                Log::error('VNPay payment failed', [
                    'booking_id' => $bookingId,
                    'response_code' => $responseCode,
                ]);
                
                return redirect()->route('account.bookings')
                    ->with('error', 'Thanh toán thất bại. Mã lỗi: ' . $responseCode);
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            
            Log::error('VNPay callback processing failed', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->route('account.bookings')
                ->with('error', 'Có lỗi xảy ra khi xử lý kết quả thanh toán.');
        }
    }
}
