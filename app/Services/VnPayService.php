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
            /** Số tiền × 100, nguyên — VNPay 2.1.0 kiểm tra khớp chuỗi khi tính checksum */
            'vnp_Amount' => (string) (int) round($amount * 100),
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

        // Chuỗi query + chuỗi checksum: giống tài liệu VNPay 2.1.0 (không dùng http_build_query — có thể lệch mã hóa).
        $hashData = '';
        $query = '';
        $i = 0;
        foreach ($inputData as $key => $value) {
            $encKey = urlencode((string) $key);
            $encVal = urlencode((string) $value);
            if ($i === 1) {
                $hashData .= '&'.$encKey.'='.$encVal;
            } else {
                $hashData .= $encKey.'='.$encVal;
                $i = 1;
            }
            $query .= $encKey.'='.$encVal.'&';
        }

        $vnpSecureHash = hash_hmac('sha512', $hashData, $vnpHashSecret);
        $fullUrl = $vnpUrl.'?'.$query.'vnp_SecureHash='.$vnpSecureHash;

        Log::info('vnpay.create_payment_url', [
            'vnp_url' => $vnpUrl,
            'vnp_tmn_code' => $vnpTmnCode,
            'amount_vnd' => $amount,
            'vnp_amount_field' => $inputData['vnp_Amount'] ?? null,
            'return_url' => $returnUrl,
            'ip_sent' => $inputData['vnp_IpAddr'] ?? null,
            'hash_data_preview' => strlen($hashData) > 120 ? substr($hashData, 0, 120).'…' : $hashData,
        ]);

        return $fullUrl;
    }

    /**
     * @param  array<string, mixed>  $inputData  Các tham số vnp_* (thường lấy từ query VNPay trả về).
     */
    public function verifyReturn(array $inputData): bool
    {
        $vnpSecureHash = (string) ($inputData['vnp_SecureHash'] ?? '');
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);

        $filtered = [];
        foreach ($inputData as $key => $value) {
            if (! is_string($key) || ! str_starts_with($key, 'vnp_')) {
                continue;
            }
            if (is_array($value)) {
                continue;
            }
            // Laravel có thể đưa null (ConvertEmptyStringsToNull) — VNPay dùng chuỗi rỗng trong checksum.
            $filtered[$key] = $value === null ? '' : (string) $value;
        }

        ksort($filtered);
        $hashData = '';
        $i = 0;
        foreach ($filtered as $key => $value) {
            $encKey = urlencode($key);
            $encVal = urlencode($value);
            if ($i === 1) {
                $hashData .= '&'.$encKey.'='.$encVal;
            } else {
                $hashData .= $encKey.'='.$encVal;
                $i = 1;
            }
        }

        $secret = (string) config('vnpay.hash_secret');
        $secureHash = hash_hmac('sha512', $hashData, $secret);

        if ($vnpSecureHash === '') {
            return false;
        }

        return hash_equals(strtolower($secureHash), strtolower($vnpSecureHash));
    }
}
