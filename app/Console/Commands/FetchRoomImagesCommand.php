<?php

namespace App\Console\Commands;

use App\Models\Image;
use App\Models\Room;
use App\Services\RoomImageDownloadService;
use App\Support\RoomImageStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FetchRoomImagesCommand extends Command
{
    protected $signature = 'room-images:fetch
                            {--force : Ghi đè ảnh đã có (local, không phải URL ngoài)}
                            {--images=4 : Số ảnh mỗi phòng}';

    protected $description = 'Tải ảnh mẫu vào thư mục room_images và cập nhật bảng images / rooms.image';

    public function handle(RoomImageDownloadService $downloader): int
    {
        $force = (bool) $this->option('force');
        $perRoom = max(1, min(8, (int) $this->option('images')));
        $urls = $downloader->sampleUrls();
        if ($urls === []) {
            $this->error('Chưa cấu hình room_images.sample_urls trong config/room_images.php');

            return 1;
        }

        RoomImageStorage::ensureDirectories();
        $disk = Storage::disk('public');
        $totalUrls = count($urls);
        $count = 0;

        foreach (Room::all() as $room) {
            if (! $force && $room->image && ! str_starts_with($room->image, 'http')) {
                $this->line("  Phòng #{$room->id} ({$room->name}) đã có ảnh local, bỏ qua (dùng --force để tải lại).");
                continue;
            }

            $savedPaths = [];
            $offset = ($room->id * $perRoom) % $totalUrls;

            for ($i = 1; $i <= $perRoom; $i++) {
                $path = RoomImageStorage::galleryPathForRoom($room->id, $i);
                $url = $urls[($offset + $i - 1) % $totalUrls];
                $content = $downloader->downloadFromUrl($url);

                if ($content === null) {
                    $this->warn("  Không tải được ảnh phòng #{$room->id} ({$i}), dùng placeholder.");
                    $content = $downloader->createPlaceholderImage(800, 600, 'R'.$room->id.'-'.$i);
                }

                $disk->put($path, $content);
                $savedPaths[] = $path;
            }

            $room->update(['image' => $savedPaths[0] ?? null]);

            Image::where('room_id', $room->id)->delete();
            foreach ($savedPaths as $path) {
                Image::create([
                    'room_id' => $room->id,
                    'image_url' => $path,
                    'image_type' => 'room',
                ]);
            }

            $count++;
            $this->line("  Phòng #{$room->id} ({$room->name}): ".count($savedPaths).' ảnh → '.$savedPaths[0]);
        }

        $this->info("Hoàn tất. Đã cập nhật {$count} phòng trong ".RoomImageStorage::roomsDir().'/' );

        return 0;
    }
}
