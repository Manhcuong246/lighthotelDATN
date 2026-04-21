<?php

namespace App\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class VnPayService
{
    /**
     * Redirect sang cổng VNPay — luôn dùng response không cache để trình duyệt/proxy
     * không giữ URL cũ (CreateDate/ExpireDate đã quá hạn) khi khách bấm link sau.
     */
    public function redirectAwayNoCache(string $paymentUrl): RedirectResponse
    {
        return redirect()->away($paymentUrl)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Validate and format IP address for VNPAY
     */
    private function validateAndFormatIp(string $ipAddr): string
    {
        // Remove any whitespace
        $ipAddr = trim($ipAddr);

        // Check if it's a valid IP address
        if (filter_var($ipAddr, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ipAddr;
        }

        // If invalid, use default server IP from config
        return config('vnpay.server_ip', '127.0.0.1');
    }

    public function createPaymentUrl(
        string $txnRef,
        int $amount,
        string $orderInfo,
        string $returnUrl,
        string $ipAddr,
        string $locale = 'vn',
        ?string $bankCode = null
    ): string {
        $vnpUrl = config('vnpay.url');
        $vnpTmnCode = config('vnpay.tmn_code');
        $vnpHashSecret = config('vnpay.hash_secret');

        // Thời điểm tạo phiên = lúc gọi hàm (khách vừa bấm “Đặt” hoặc vừa mở link thanh toán).
        // App timezone (config/app.php) phải là GMT+7 theo tài liệu VNPay cho yyyyMMddHHmmss.
        $createDate = now();
        $expireMinutes = max(5, min(1440, (int) config('vnpay.transaction_expire_minutes', 30)));
        $expireDate = $createDate->copy()->addMinutes($expireMinutes);

        // Ensure proper encoding for Vietnamese characters
        $orderInfo = mb_convert_encoding($orderInfo, 'UTF-8', 'UTF-8');

        $inputData = [
            'vnp_Version' => '2.1.0',
            'vnp_TmnCode' => $vnpTmnCode,
            'vnp_Amount' => $amount * 100,
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => $createDate->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => $this->validateAndFormatIp($ipAddr),
            'vnp_Locale' => $locale,
            'vnp_OrderInfo' => $orderInfo,
            'vnp_OrderType' => 'other',
            'vnp_ReturnUrl' => $returnUrl,
            'vnp_TxnRef' => $txnRef,
            'vnp_ExpireDate' => $expireDate->format('YmdHis'),
        ];

        if ($bankCode) {
            $inputData['vnp_BankCode'] = $bankCode;
        }

        ksort($inputData);

        // Log all input data for debugging
        \Log::info('VNPAY Payment Data:', [
            'input_data' => $inputData,
            'vnp_url' => $vnpUrl,
            'vnp_tmn_code' => $vnpTmnCode,
            'amount' => $amount,
            'order_info' => $orderInfo,
            'return_url' => $returnUrl,
            'ip_addr' => $ipAddr,
        ]);

        $hashData = '';
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i === 1) {
                $hashData .= '&' . urlencode($key) . '=' . urlencode($value);
            } else {
                $hashData .= urlencode($key) . '=' . urlencode($value);
                $i = 1;
            }
        }

        \Log::info('VNPAY Hash Data:', ['hash_data' => $hashData]);

        $vnpSecureHash = hash_hmac('sha512', $hashData, $vnpHashSecret);
        $query = http_build_query($inputData);

        $fullUrl = $vnpUrl . '?' . $query . '&vnp_SecureHash=' . $vnpSecureHash;

        \Log::info('VNPAY Full URL Generated:', ['url' => $fullUrl]);

        return $fullUrl;
    }

    public function verifyReturn(array $inputData): bool
    {
        $vnpSecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);

        ksort($inputData);
        $hashData = '';
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i === 1) {
                $hashData .= '&' . urlencode($key) . '=' . urlencode($value);
            } else {
                $hashData .= urlencode($key) . '=' . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, config('vnpay.hash_secret'));

        return hash_equals($secureHash, $vnpSecureHash);
    }
}
