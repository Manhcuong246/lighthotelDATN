<?php

namespace App\Console\Commands;

use App\Models\Image;
use App\Models\Room;
use App\Services\RoomImageDownloadService;
use App\Support\RoomImageStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ReplaceAllRoomImagesCommand extends Command
{
    protected $signature = 'room-images:replace-all
                            {--per-room=4 : Số ảnh mỗi phòng (tối đa 8)}
                            {--dry-run : Chỉ in kế hoạch, không xóa/tải/ghi DB}';

    protected $description = 'Xóa ảnh phòng cũ + tải bộ mới: ưu tiên Pexels (phòng khách sạn), sau đó Unsplash/Picsum — không dùng placehold.co';

    private const MAX_SLOTS = 8;

    public function handle(RoomImageDownloadService $downloader): int
    {
        $perRoom = max(1, min(self::MAX_SLOTS, (int) $this->option('per-room')));
        $dry = (bool) $this->option('dry-run');

        $pexels = config('room_images.sample_urls', []);
        $unsplash = config('room_images.unsplash_room_urls', []);
        $candidatesPool = array_values(array_unique(array_merge(
            is_array($pexels) ? $pexels : [],
            is_array($unsplash) ? $unsplash : [],
        )));
        if ($candidatesPool === []) {
            $this->error('Cần URL trong config: room_images.sample_urls (Pexels) và/hoặc room_images.unsplash_room_urls.');

            return 1;
        }

        $urlCount = count($candidatesPool);
        RoomImageStorage::ensureDirectories();

        $disk = Storage::disk('public');
        $roomsDir = RoomImageStorage::roomsDir();
        $poolDir = RoomImageStorage::poolDir();

        if ($dry) {
            $this->warn('[dry-run] Sẽ xóa file trong '.$roomsDir.' và '.$poolDir.', xóa images phòng trong DB, rồi tải từ '.$urlCount.' URL (Pexels+Unsplash).');
        } else {
            $imgRows = Image::whereNotNull('room_id')->count();
            Image::whereNotNull('room_id')->delete();
            Room::query()->update(['image' => null]);
            $this->info("Đã xóa {$imgRows} dòng trong bảng images (room_id) và reset rooms.image.");

            foreach ([$roomsDir, $poolDir] as $dir) {
                if (! $disk->exists($dir)) {
                    continue;
                }
                foreach ($disk->files($dir) as $file) {
                    $disk->delete($file);
                }
            }
            $this->info('Đã xóa file trong '.$roomsDir.' và '.$poolDir.'.');
        }

        $rooms = Room::query()->orderBy('id')->get();
        $this->newLine();
        $this->info('Đang tải và gán ảnh cho '.$rooms->count()." phòng ({$perRoom} ảnh/phòng — ưu tiên Pexels, ảnh phòng khách sạn)...");

        $ok = 0;
        $seedBase = time();
        foreach ($rooms as $room) {
            $saved = [];
            for ($j = 1; $j <= $perRoom; $j++) {
                $k = (($room->id - 1) * $perRoom + $j - 1);
                $dest = RoomImageStorage::galleryPathForRoom($room->id, $j);

                $tryUrls = [
                    $candidatesPool[$k % $urlCount],
                    $candidatesPool[($k + max(1, (int) ($urlCount / 3))) % $urlCount],
                    $candidatesPool[($k + max(1, (int) (2 * $urlCount / 3))) % $urlCount],
                    'https://picsum.photos/seed/hotel'.$seedBase.'_r'.$room->id.'_'.$j.'/1200/800',
                    'https://picsum.photos/1200/800?random='.($seedBase + $k),
                ];

                if ($dry) {
                    $this->line("  [dry] #{$room->id} {$dest} <= ưu tiên: ".$tryUrls[0]);

                    continue;
                }

                $content = $downloader->downloadFirstOk($tryUrls);
                if ($content === null) {
                    $content = $downloader->createPlaceholderImage(800, 600, 'R'.$room->id.'-'.$j);
                    $this->warn("  Phòng #{$room->id} ảnh {$j}: không tải được từ mạng, dùng placeholder GD (cài đủ extension trong Docker).");
                }

                $disk->put($dest, $content);
                $saved[] = $dest;
            }

            if ($dry) {
                continue;
            }

            $room->update(['image' => $saved[0] ?? null]);
            foreach ($saved as $path) {
                Image::create([
                    'room_id' => $room->id,
                    'image_url' => $path,
                    'image_type' => 'room',
                ]);
            }
            $ok++;
            $bytes = isset($saved[0]) && $disk->exists($saved[0]) ? $disk->size($saved[0]) : 0;
            $this->line("  Phòng #{$room->id} ({$room->name}): {$perRoom} ảnh — file đầu ~".(int) ($bytes / 1024).' KB');
        }

        if (! $dry) {
            $this->newLine();
            $this->info("Hoàn tất. Đã cập nhật {$ok} phòng. Đường dẫn DB: {$roomsDir}/room_{id}_{1..{$perRoom}}.jpg");
        }

        return 0;
    }
}
