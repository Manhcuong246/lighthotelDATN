<?php

namespace App\Console\Commands;

use App\Models\Image;
use App\Models\Room;
use App\Models\RoomType;
use App\Support\RoomImageStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class RefreshRoomImagesCommand extends Command
{
    protected $signature = 'images:rooms-refresh';

    protected $description = 'Xóa toàn bộ ảnh phòng, tải lại ảnh khách sạn mới';

    public function handle(): int
    {
        $this->info('Bước 1: Xóa toàn bộ ảnh phòng...');

        $deletedImages = Image::whereNotNull('room_id')->count();
        Image::whereNotNull('room_id')->delete();
        $this->line("  Đã xóa {$deletedImages} bản ghi trong bảng images.");

        Room::query()->update(['image' => null]);
        $this->line('  Đã xóa cột image của rooms.');

        $roomTypes = RoomType::whereNotNull('image')->where('image', '!=', '')->get();
        foreach ($roomTypes as $rt) {
            $path = $this->getStoragePath($rt->image);
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            $rt->update(['image' => null]);
        }
        $this->line('  Đã xóa ảnh room_types.');

        $disk = Storage::disk('public');
        foreach (['rooms', RoomImageStorage::roomsDir()] as $dir) {
            if ($disk->exists($dir)) {
                foreach ($disk->files($dir) as $file) {
                    $disk->delete($file);
                }
            }
        }
        $this->line('  Đã xóa file ảnh phòng trong thư mục cũ (rooms/) và mới (room_images/rooms/).');

        $this->newLine();
        $this->info('Bước 2: Tải ảnh phòng khách sạn mới...');

        RoomImageStorage::ensureDirectories();

        $rooms = Room::all();
        $urls = config('room_images.sample_urls', []);
        $totalUrls = count($urls);
        if ($totalUrls === 0) {
            $this->error('Thiếu sample_urls trong config/room_images.php');

            return 1;
        }
        $count = 0;

        foreach ($rooms as $room) {
            $savedPaths = [];
            $imagesPerRoom = 4;
            $offset = ($room->id * $imagesPerRoom) % $totalUrls;

            for ($i = 1; $i <= $imagesPerRoom; $i++) {
                $path = RoomImageStorage::galleryPathForRoom($room->id, $i);
                $url = $urls[($offset + $i - 1) % $totalUrls];
                $content = $this->downloadImage($url);

                if ($content === null) {
                    $this->warn("  Không tải được ảnh phòng #{$room->id} (ảnh {$i}), tạo placeholder.");
                    $content = $this->createPlaceholderImage(800, 600, 'R' . $room->id . '-' . $i);
                }

                Storage::disk('public')->put($path, $content);
                $savedPaths[] = $path;
            }

            $room->update(['image' => $savedPaths[0]]);

            foreach ($savedPaths as $path) {
                Image::create([
                    'room_id' => $room->id,
                    'image_url' => $path,
                    'image_type' => 'room',
                ]);
            }

            $count++;
            $this->line("  Phòng #{$room->id} ({$room->name}): " . count($savedPaths) . ' ảnh');
        }

        $this->newLine();
        $this->info("Hoàn tất. Đã cập nhật ảnh cho {$count} phòng.");

        return 0;
    }

    private function getStoragePath(?string $path): ?string
    {
        if (empty($path) || str_starts_with($path, 'http')) {
            return null;
        }
        return ltrim($path, '/');
    }

    private function downloadImage(string $url): ?string
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'image/*',
                ])
                ->get($url);

            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Throwable $e) {
            $this->warn("  Lỗi tải ảnh: {$e->getMessage()}");
        }

        return null;
    }

    private function createPlaceholderImage(int $w, int $h, string $label): string
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

    private function createMinimalJpeg(): string
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
}
