<?php

namespace App\Services;

use Illuminate\Http\RedirectResponse;

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

        $inputData = [
            'vnp_Version' => '2.1.0',
            'vnp_TmnCode' => $vnpTmnCode,
            'vnp_Amount' => $amount * 100,
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => $createDate->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => $ipAddr,
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

        $vnpSecureHash = hash_hmac('sha512', $hashData, $vnpHashSecret);
        $query = http_build_query($inputData);

        return $vnpUrl . '?' . $query . '&vnp_SecureHash=' . $vnpSecureHash;
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
