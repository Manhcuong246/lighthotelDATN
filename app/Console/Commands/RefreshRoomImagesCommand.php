<?php

namespace App\Console\Commands;

use App\Models\Image;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class RefreshRoomImagesCommand extends Command
{
    protected $signature = 'images:rooms-refresh';

    protected $description = 'Xóa toàn bộ ảnh phòng, tải lại ảnh khách sạn mới';

    /** Ảnh phòng khách sạn - Pexels (phòng ngủ, view, phòng tắm...) */
    private array $roomImageUrls = [
        'https://images.pexels.com/photos/164595/pexels-photo-164595.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271619/pexels-photo-271619.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/262048/pexels-photo-262048.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/279746/pexels-photo-279746.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271624/pexels-photo-271624.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271816/pexels-photo-271816.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/1457842/pexels-photo-1457842.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/2376997/pexels-photo-2376997.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/212269/pexels-photo-212269.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271897/pexels-photo-271897.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/14746032/pexels-photo-14746032.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/10343928/pexels-photo-10343928.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/10389176/pexels-photo-10389176.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/5439496/pexels-photo-5439496.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/14021931/pexels-photo-14021931.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/3940733/pexels-photo-3940733.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/13722872/pexels-photo-13722872.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/3688261/pexels-photo-3688261.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/1579253/pexels-photo-1579253.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271643/pexels-photo-271643.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/276224/pexels-photo-276224.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271706/pexels-photo-271706.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271707/pexels-photo-271707.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271708/pexels-photo-271708.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271711/pexels-photo-271711.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271713/pexels-photo-271713.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271715/pexels-photo-271715.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271716/pexels-photo-271716.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/1643383/pexels-photo-1643383.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/189295/pexels-photo-189295.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271618/pexels-photo-271618.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271612/pexels-photo-271612.jpeg?auto=compress&cs=tinysrgb&w=800',
    ];

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

        $files = Storage::disk('public')->files('rooms');
        foreach ($files as $file) {
            Storage::disk('public')->delete($file);
        }
        $this->line('  Đã xóa ' . count($files) . ' file trong storage/rooms.');

        $this->newLine();
        $this->info('Bước 2: Tải ảnh phòng khách sạn mới...');

        Storage::disk('public')->makeDirectory('rooms');

        $rooms = Room::all();
        $totalUrls = count($this->roomImageUrls);
        $count = 0;

        foreach ($rooms as $room) {
            $savedPaths = [];
            $imagesPerRoom = 4;
            $offset = ($room->id * $imagesPerRoom) % $totalUrls;

            for ($i = 0; $i < $imagesPerRoom; $i++) {
                $path = 'rooms/room_' . $room->id . '_' . ($i + 1) . '.jpg';
                $url = $this->roomImageUrls[($offset + $i) % $totalUrls];
                $content = $this->downloadImage($url);

                if ($content === null) {
                    $this->warn("  Không tải được ảnh phòng #{$room->id} (ảnh " . ($i + 1) . "), tạo placeholder.");
                    $content = $this->createPlaceholderImage(800, 600, 'R' . $room->id . '-' . ($i + 1));
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
