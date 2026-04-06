<?php

namespace App\Console\Commands;

use App\Models\Image;
use App\Models\Room;
use App\Models\RoomType;
use App\Support\RoomImageStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateLegacyRoomImagePathsCommand extends Command
{
    protected $signature = 'room-images:migrate-legacy
                            {--delete-old : Xóa file ở thư mục cũ (rooms/, room_types/) sau khi copy}';

    protected $description = 'Copy ảnh từ rooms/ và room_types/ sang room_images/... và cập nhật đường dẫn trong DB';

    public function handle(): int
    {
        RoomImageStorage::ensureDirectories();
        $disk = Storage::disk('public');
        $deleteOld = (bool) $this->option('delete-old');

        $migrated = 0;

        foreach (Image::whereNotNull('image_url')->get() as $img) {
            $url = $img->image_url;
            if (empty($url) || str_starts_with($url, 'http')) {
                continue;
            }
            $url = ltrim($url, '/');
            if (str_starts_with($url, RoomImageStorage::baseDir().'/')) {
                continue;
            }

            $newPath = $this->mapLegacyPath($url);
            if ($newPath === null || $newPath === $url) {
                continue;
            }

            if ($disk->exists($url)) {
                if (! $disk->exists($newPath)) {
                    $disk->copy($url, $newPath);
                }
                $img->update(['image_url' => $newPath]);
                $migrated++;
                if ($deleteOld && $disk->exists($url)) {
                    $disk->delete($url);
                }
            }
        }

        foreach (Room::all() as $room) {
            $p = $room->image;
            if (empty($p) || str_starts_with($p, 'http')) {
                continue;
            }
            $p = ltrim($p, '/');
            if (str_starts_with($p, RoomImageStorage::baseDir().'/')) {
                continue;
            }
            $newPath = $this->mapLegacyPath($p);
            if ($newPath === null || $newPath === $p) {
                continue;
            }
            if ($disk->exists($p)) {
                if (! $disk->exists($newPath)) {
                    $disk->copy($p, $newPath);
                }
                $room->update(['image' => $newPath]);
                $migrated++;
                if ($deleteOld && $disk->exists($p)) {
                    $disk->delete($p);
                }
            }
        }

        foreach (RoomType::all() as $rt) {
            $p = $rt->image;
            if (empty($p) || str_starts_with($p, 'http')) {
                continue;
            }
            $p = ltrim($p, '/');
            if (str_starts_with($p, RoomImageStorage::baseDir().'/')) {
                continue;
            }
            $newPath = $this->mapLegacyPath($p);
            if ($newPath === null || $newPath === $p) {
                continue;
            }
            if ($disk->exists($p)) {
                if (! $disk->exists($newPath)) {
                    $disk->copy($p, $newPath);
                }
                $rt->update(['image' => $newPath]);
                $migrated++;
                if ($deleteOld && $disk->exists($p)) {
                    $disk->delete($p);
                }
            }
        }

        $this->info("Đã xử lý {$migrated} cập nhật đường dẫn (chỉ khi file nguồn tồn tại).");

        return 0;
    }

    private function mapLegacyPath(string $path): ?string
    {
        if (str_starts_with($path, 'rooms/')) {
            return RoomImageStorage::roomsDir().'/'.substr($path, strlen('rooms/'));
        }
        if (str_starts_with($path, 'room_types/')) {
            return RoomImageStorage::roomTypesDir().'/'.substr($path, strlen('room_types/'));
        }

        return null;
    }
}
