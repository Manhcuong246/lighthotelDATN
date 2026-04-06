<?php

namespace App\Console\Commands;

use App\Models\Image;
use App\Models\Room;
use App\Services\RoomImageDownloadService;
use App\Support\RoomImageStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncRoomImagesFromPoolCommand extends Command
{
    protected $signature = 'room-images:pool-sync
                            {--count=48 : Số ảnh tải vào thư mục pool (pexels + dự phòng)}
                            {--per-room=4 : Số ảnh gán cho mỗi phòng}
                            {--skip-download : Không tải mới, chỉ gán lại từ pool hiện có}
                            {--fresh : Xóa file trong pool trước khi tải (chỉ khi không --skip-download)}';

    protected $description = 'Tải nhiều ảnh vào storage/app/public/room_images/pool rồi gán cho mọi phòng (copy sang room_images/rooms, cập nhật DB)';

    private const MAX_GALLERY_SLOTS = 8;

    public function handle(RoomImageDownloadService $downloader): int
    {
        $skipDownload = (bool) $this->option('skip-download');
        $count = max(1, (int) $this->option('count'));
        $perRoom = max(1, min(self::MAX_GALLERY_SLOTS, (int) $this->option('per-room')));
        $fresh = (bool) $this->option('fresh');

        RoomImageStorage::ensureDirectories();

        $disk = Storage::disk('public');
        $poolDir = RoomImageStorage::poolDir();

        if (! $skipDownload) {
            if ($fresh) {
                $this->clearPoolDirectory($disk, $poolDir);
                $this->line("Đã xóa file cũ trong {$poolDir}/");
            }

            $this->info("Tải {$count} ảnh vào {$poolDir}/ (ưu tiên URL trong config/room_images.php)...");
            $samples = $downloader->sampleUrls();
            $seedBase = time();
            for ($n = 1; $n <= $count; $n++) {
                $candidates = [];
                if ($samples !== []) {
                    $candidates[] = $samples[($n - 1) % count($samples)];
                    $candidates[] = $samples[($n * 11 + 3) % count($samples)];
                }
                $candidates[] = 'https://picsum.photos/seed/pool'.$seedBase.'_'.$n.'/800/600';
                $candidates[] = 'https://picsum.photos/800/600?random='.(int) ($seedBase + $n);
                $candidates[] = 'https://placehold.co/800x600.jpeg?text=Room+'.sprintf('%04d', $n);

                $content = $downloader->downloadFirstOk($candidates);
                if ($content === null) {
                    $this->warn("  Không tải được ảnh #{$n} (mạng/chặn URL), dùng placeholder.");
                    $content = $downloader->createPlaceholderImage(800, 600, 'P'.$n);
                } else {
                    $kb = (int) (strlen($content) / 1024);
                    $this->line("  img_{$n}: OK (~{$kb} KB)");
                }
                $path = $poolDir.'/img_'.sprintf('%04d', $n).'.jpg';
                $disk->put($path, $content);
                $this->line("  → {$path}");
            }
        }

        $poolFiles = $this->listPoolImageFiles($disk, $poolDir);
        if ($poolFiles->isEmpty()) {
            $this->error("Không có ảnh trong pool ({$poolDir}). Bỏ --skip-download hoặc kiểm tra thư mục.");

            return 1;
        }

        $this->newLine();
        $this->info('Gán ảnh từ pool cho '.Room::count().' phòng (per-room='.$perRoom.')...');

        foreach (Room::query()->orderBy('id')->get() as $room) {
            Image::where('room_id', $room->id)->delete();
            $this->deleteRoomGalleryFiles($disk, $room->id);

            $savedPaths = [];
            for ($j = 1; $j <= $perRoom; $j++) {
                $idx = (($room->id - 1) * $perRoom + $j - 1) % $poolFiles->count();
                $src = $poolFiles[$idx];
                $dest = RoomImageStorage::galleryPathForRoom($room->id, $j);
                if (! $disk->exists($src)) {
                    $this->warn("  Thiếu file pool: {$src}");
                    continue;
                }
                $disk->copy($src, $dest);
                $savedPaths[] = $dest;
            }

            if ($savedPaths === []) {
                $this->warn("  Phòng #{$room->id}: không gán được ảnh.");
                $room->update(['image' => null]);
                continue;
            }

            $room->update(['image' => $savedPaths[0]]);
            foreach ($savedPaths as $path) {
                Image::create([
                    'room_id' => $room->id,
                    'image_url' => $path,
                    'image_type' => 'room',
                ]);
            }

            $this->line("  Phòng #{$room->id} ({$room->name}): ".count($savedPaths).' ảnh');
        }

        $this->newLine();
        $this->info('Hoàn tất. Pool: '.$poolFiles->count().' file; mỗi phòng lưu bản copy trong '.RoomImageStorage::roomsDir().'/');

        return 0;
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function listPoolImageFiles(\Illuminate\Contracts\Filesystem\Filesystem $disk, string $poolDir)
    {
        if (! $disk->exists($poolDir)) {
            return collect();
        }

        return collect($disk->files($poolDir))
            ->filter(fn (string $f) => (bool) preg_match('/\.(jpe?g|png|gif|webp)$/i', $f))
            ->sort()
            ->values();
    }

    private function clearPoolDirectory(\Illuminate\Contracts\Filesystem\Filesystem $disk, string $poolDir): void
    {
        if (! $disk->exists($poolDir)) {
            return;
        }
        foreach ($disk->files($poolDir) as $file) {
            $disk->delete($file);
        }
    }

    private function deleteRoomGalleryFiles(\Illuminate\Contracts\Filesystem\Filesystem $disk, int $roomId): void
    {
        for ($i = 1; $i <= self::MAX_GALLERY_SLOTS; $i++) {
            $p = RoomImageStorage::galleryPathForRoom($roomId, $i);
            if ($disk->exists($p)) {
                $disk->delete($p);
            }
        }
    }
}
