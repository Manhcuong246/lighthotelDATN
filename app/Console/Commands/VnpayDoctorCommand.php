<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class VnpayDoctorCommand extends Command
{
    protected $signature = 'vnpay:doctor';

    protected $description = 'Kiểm tra cấu hình VNPay (APP_URL, return URL, cổng sandbox/production, proxy)';

    public function handle(): int
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $returnUrl = route('payment.vnpay.return');
        $vnpUrl = (string) config('vnpay.url');
        $tmn = (string) config('vnpay.tmn_code');
        $secret = (string) config('vnpay.hash_secret');
        $envTrusted = env('TRUSTED_PROXIES');

        $vnpayConfigured = ($tmn !== '' && $secret !== '');
        $appLooksLocal = str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1');

        $this->info('=== VNPay / thanh toán ===');
        $this->line('APP_URL:           '.$appUrl);
        $this->line('Return URL (vnp): '.$returnUrl);
        $this->line('VNPay gateway:     '.$vnpUrl);
        $this->line('TMN code:          '.($tmn !== '' ? $tmn : '(trống — VNPay tắt, dùng tiền mặt/CK)'));
        $this->line('Hash secret:       '.($secret !== '' ? str_repeat('*', min(12, strlen($secret))).' ('.strlen($secret).' ký tự)' : '(trống)'));
        $this->line('TRUSTED_PROXIES:   '.($envTrusted !== null && $envTrusted !== '' ? $envTrusted : '(trống — sau reverse proxy cần * hoặc IP proxy)'));
        $this->line('Timezone:          '.config('app.timezone'));
        $this->newLine();

        $issues = 0;
        if ($vnpayConfigured && ! str_starts_with($appUrl, 'https://') && str_contains($vnpUrl, 'sandbox.vnpayment.vn')) {
            $this->warn('→ Sandbox VNPay: nên dùng APP_URL https công khai (ngrok / hosting) để ReturnUrl VNPay gọi được từ internet.');
            $issues++;
        }

        if ($vnpayConfigured && ($appUrl === '' || $appUrl === 'http://localhost')) {
            $this->error('→ APP_URL không hợp lệ cho thanh toán online.');
            $issues++;
        }

        if ($vnpayConfigured && $appLooksLocal) {
            $this->warn('→ VNPay không gọi được ReturnUrl tới localhost từ máy chủ của họ — dùng tunnel (ngrok) hoặc domain thật.');
            $issues++;
        }

        if (! $vnpayConfigured && $appLooksLocal) {
            $this->info('→ Chạy local: TMN/secret trống — luồng VNPay tắt (đặt phòng bằng tiền mặt/CK).');
        } elseif (! $vnpayConfigured) {
            $this->error('→ Thiếu VNPAY_TMN_CODE hoặc VNPAY_HASH_SECRET trong .env (cần nếu bật thanh toán VNPay).');
            $issues++;
        }

        if (($envTrusted === null || $envTrusted === '') && str_starts_with($appUrl, 'https://')) {
            $this->warn('→ HTTPS + load balancer/ngrok: thường cần TRUSTED_PROXIES=* trong .env (đã có comment trong .env.example).');
        }

        if ($vnpayConfigured) {
            $this->info('Đăng ký Return URL trên cổng merchant VNPay khớp tuyệt đối:');
            $this->line('  '.$returnUrl);
            $this->newLine();

            try {
                $head = Http::timeout(10)->head($vnpUrl);
                if ($head->successful() || $head->status() === 405 || $head->status() === 403) {
                    $this->info('Cổng VNPay phản hồi (HTTP '.$head->status().') — mạng tới '.$vnpUrl.' OK.');
                } else {
                    $this->warn('Cổng VNPay HTTP '.$head->status().' — kiểm tra firewall/DNS.');
                    $issues++;
                }
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                if (stripos($msg, 'SSL certificate problem') !== false || stripos($msg, 'curl error 60') !== false) {
                    $this->warn('Không verify được HTTPS tới VNPay từ PHP trên máy này (thường gặp Windows thiếu CA bundle). Trình duyệt khách vẫn có thể thanh toán bình thường; server Linux/production thường ổn.');
                    $this->line('  Gợi ý: bật curl.cainfo trong php.ini trỏ tới file cacert.pem, hoặc cài openssl đầy đủ.');
                } else {
                    $this->error('Không kết nối được tới '.$vnpUrl.': '.$msg);
                    $issues++;
                }
            }
        }

        $this->newLine();
        if ($issues === 0) {
            $this->info('Không phát hiện lỗi cấu hình rõ ràng. Nếu vẫn lỗi khi bấm thanh toán: xem storage/logs/laravel.log (vnpay.create_payment_url), kiểm tra TMN/Secret đúng sandbox, và URL Return đã khai báo trên VNPay.');

            return self::SUCCESS;
        }

        $this->warn("Có {$issues} cảnh báo/lỗi — xử lý trước khi test online.");

        return self::FAILURE;
    }
}
