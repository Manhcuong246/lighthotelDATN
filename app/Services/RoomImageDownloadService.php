<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RoomImageDownloadService
{
    public function downloadFromUrl(string $url): ?string
    {
        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'image/*',
                ])
                ->get($url);

            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Throwable) {
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

    public function createMinimalJpeg(): string
    {
        $img = imagecreatetruecolor(1, 1);
        if ($img === false) {
            return '';
        }
        $bg = imagecolorallocate($img, 200, 200, 200);
        imagefill($img, 0, 0, $bg);
        ob_start();
        imagejpeg($img, null, 85);
        $content = ob_get_clean();
        imagedestroy($img);

        return $content ?? '';
    }

    /** @return list<string> */
    public function sampleUrls(): array
    {
        return config('room_images.sample_urls', []);
    }
}
