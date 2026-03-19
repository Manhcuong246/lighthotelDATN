<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncNgrokUrlCommand extends Command
{
    protected $signature = 'app:sync-ngrok-url
                            {--env= : Path to .env file (default: base .env)}
                            {--api=http://127.0.0.1:4040/api/tunnels : Ngrok local API URL}
                            {--prefer=https : Prefer https or http tunnel}';

    protected $description = 'Sync APP_URL in .env from current ngrok public URL';

    public function handle(): int
    {
        $envPath = $this->option('env') ?: base_path('.env');
        $apiUrl = (string) $this->option('api');
        $prefer = strtolower((string) $this->option('prefer')) === 'http' ? 'http' : 'https';

        if (! is_readable($envPath) || ! is_writable($envPath)) {
            $this->error("Không đọc/ghi được file env: {$envPath}");
            return 1;
        }

        try {
            $resp = Http::timeout(3)->get($apiUrl);
            if (! $resp->ok()) {
                $this->error("Không gọi được ngrok API ({$apiUrl}), status={$resp->status()}");
                return 1;
            }
            $data = $resp->json();
        } catch (\Throwable $e) {
            $this->error("Không gọi được ngrok API ({$apiUrl}): {$e->getMessage()}");
            return 1;
        }

        $tunnels = is_array($data['tunnels'] ?? null) ? $data['tunnels'] : [];
        if (empty($tunnels)) {
            $this->error('Ngrok chưa tạo tunnel nào. Hãy chạy ngrok trước.');
            return 1;
        }

        $publicUrl = $this->pickPublicUrl($tunnels, $prefer);
        if (! $publicUrl) {
            $this->error('Không tìm được public_url từ ngrok tunnels.');
            return 1;
        }

        $env = file_get_contents($envPath);
        if ($env === false) {
            $this->error("Không đọc được file env: {$envPath}");
            return 1;
        }

        $newEnv = $this->upsertEnvKey($env, 'APP_URL', $publicUrl);
        if ($newEnv === $env) {
            $this->info("APP_URL không đổi: {$publicUrl}");
            return 0;
        }

        $ok = file_put_contents($envPath, $newEnv);
        if ($ok === false) {
            $this->error("Không ghi được file env: {$envPath}");
            return 1;
        }

        $this->info("Đã cập nhật APP_URL={$publicUrl}");
        $this->line('Gợi ý: chạy `php artisan config:clear` nếu bạn đang cache config.');

        return 0;
    }

    private function pickPublicUrl(array $tunnels, string $prefer): ?string
    {
        $preferred = null;
        $fallback = null;

        foreach ($tunnels as $t) {
            $url = $t['public_url'] ?? null;
            if (! is_string($url) || $url === '') {
                continue;
            }
            if (str_starts_with($url, $prefer . '://')) {
                $preferred = $url;
                break;
            }
            if (! $fallback && (str_starts_with($url, 'https://') || str_starts_with($url, 'http://'))) {
                $fallback = $url;
            }
        }

        return $preferred ?: $fallback;
    }

    private function upsertEnvKey(string $env, string $key, string $value): string
    {
        $line = $key . '=' . $value;

        if (preg_match('/^' . preg_quote($key, '/') . '=.*/m', $env)) {
            return preg_replace('/^' . preg_quote($key, '/') . '=.*/m', $line, $env) ?? $env;
        }

        $env = rtrim($env) . PHP_EOL;
        return $env . $line . PHP_EOL;
    }
}

