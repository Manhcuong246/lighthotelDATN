<?php

namespace App\Services;

use Carbon\Carbon;

class VnPayService
{
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

        // vnp_CreateDate / vnp_ExpireDate gắn với lần gọi hàm này (khi redirect sang VNPay sau khi khách bấm link).
        $createDate = Carbon::now('Asia/Ho_Chi_Minh');
        $expireMinutes = max(5, (int) config('vnpay.transaction_expire_minutes', 15));
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
