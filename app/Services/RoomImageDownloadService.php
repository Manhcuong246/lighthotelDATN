<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RoomImageDownloadService
{
    public function downloadFromUrl(string $url): ?string
    {
        try {
            $response = Http::timeout(35)
                ->connectTimeout(15)
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    'Accept' => 'image/jpeg,image/png,image/*;q=0.8,*/*;q=0.5',
                ])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }
            $body = $response->body();
            if ($body === '' || ! $this->isProbableImageBody($body)) {
                return null;
            }

            return $body;
        } catch (\Throwable) {
        }

        return null;
    }

    /** Có đủ byte và magic header ảnh; loại HTML/JSON lỗi. */
    public function isProbableImageBody(string $body): bool
    {
        $len = strlen($body);
        if ($len < 500) {
            return false;
        }
        $t = ltrim($body);
        if ($t !== '' && ($t[0] === '<' || str_starts_with($t, '{') || str_starts_with($t, '['))) {
            return false;
        }
        $h = substr($body, 0, 16);

        return str_starts_with($h, "\xFF\xD8\xFF")
            || str_starts_with($h, "\x89PNG\r\n\x1a\n")
            || (str_starts_with($h, 'RIFF') && str_contains(substr($body, 0, 24), 'WEBP'));
    }

    /**
     * Thử lần lượt nhiều URL cho đến khi có ảnh hợp lệ.
     *
     * @param  list<string>  $urls
     */
    public function downloadFirstOk(array $urls): ?string
    {
        foreach (array_values(array_unique(array_filter($urls))) as $url) {
            $body = $this->downloadFromUrl($url);
            if ($body !== null) {
                return $body;
            }
        }

        return null;
    }

    public function createPlaceholderImage(int $w, int $h, string $label): string
    {
        if (! extension_loaded('gd')) {
            return $this->createMinimalJpeg();
        }

        $img = imagecreatetruecolor($w, $h);
        if ($img === false) {
            return $this->createMinimalJpeg();
        }

        $bg = imagecolorallocate($img, 180, 200, 220);
        $text = imagecolorallocate($img, 80, 90, 100);
        imagefill($img, 0, 0, $bg);

        $fontSize = 4;
        $x = (int) (($w - strlen($label) * imagefontwidth($fontSize)) / 2);
        $y = (int) (($h - imagefontheight($fontSize)) / 2);
        imagestring($img, $fontSize, max(0, $x), max(0, $y), $label, $text);

        ob_start();
        imagejpeg($img, null, 85);
        $content = ob_get_clean();
        imagedestroy($img);

        return $content ?: $this->createMinimalJpeg();
    }

    /**
     * JPEG 1×1 pixel hợp lệ, dùng khi không có GD hoặc GD lỗi.
     */
    public function createMinimalJpeg(): string
    {
        if (extension_loaded('gd')) {
            $img = imagecreatetruecolor(1, 1);
            if ($img !== false) {
                $bg = imagecolorallocate($img, 200, 200, 200);
                imagefill($img, 0, 0, $bg);
                ob_start();
                imagejpeg($img, null, 85);
                $content = ob_get_clean();
                imagedestroy($img);
                if ($content !== false && $content !== '') {
                    return $content;
                }
            }
        }

        $b64 = '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDAREAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCwAA8A/9k=';

        return base64_decode($b64, true) ?: '';
    }

    /** @return list<string> */
    public function sampleUrls(): array
    {
        return config('room_images.sample_urls', []);
    }
}
