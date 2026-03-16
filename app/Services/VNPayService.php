<?php

namespace App\Services;

class VNPayService
{
    protected $vnp_Url;
    protected $vnp_ReturnUrl;
    protected $vnp_TmnCode;
    protected $vnp_HashSecret;

    public function __construct()
    {
        // Cấu hình VNPay
        $this->vnp_TmnCode = config('services.vnpay.tmn_code'); // Mã TMN từ VNPay
        $this->vnp_HashSecret = config('services.vnpay.hash_secret'); // Mã bí mật
        $this->vnp_Url = config('services.vnpay.url'); // URL cổng thanh toán
        $this->vnp_ReturnUrl = route('payment.callback'); // URL nhận kết quả
    }

    /**
     * Tạo payment URL cho VNPay
     */
    public function createPaymentUrl($booking, $amount)
    {
        $time = now()->timestamp;
        
        $vnp_TxnRef = $booking->id; // Mã giao dịch
        $vnp_OrderInfo = 'Thanh toan dat coc booking #' . $booking->id;
        $vnp_OrderType = 'billpayment';
        $vnp_Amount = $amount * 100; // Số tiền * 100 (theo quy định VNPay)
        $vnp_Locale = 'vn'; // Ngôn ngữ hiển thị
        $vnp_BankCode = ''; // Để trống để khách chọn ngân hàng
        $vnp_IpAddr = request()->ip(); // IP client

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $this->vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis', $time),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $this->vnp_ReturnUrl,
            "vnp_TxnRef" => $vnp_TxnRef,
        ];

        if (isset($vnp_BankCode) && $vnp_BankCode != "") {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }

        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";

        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $this->vnp_Url . "?" . $query;

        if (isset($this->vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $this->vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }

        return $vnp_Url;
    }

    /**
     * Xác thực response từ VNPay
     */
    public function validateResponse($requestData)
    {
        $vnp_SecureHash = $requestData['vnp_SecureHash'];
        unset($requestData['vnp_SecureHash']);
        unset($requestData['vnp_SecureHashType']);

        ksort($requestData);
        $i = 0;
        $hashData = "";

        foreach ($requestData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $this->vnp_HashSecret);

        return $secureHash === $vnp_SecureHash;
    }
}
