<?php

namespace App\Console\Commands;

use App\Models\Image;
use App\Models\Room;
use App\Models\User;
use App\Support\RoomImageStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SeedImagesCommand extends Command
{
    protected $signature = 'images:seed
                            {--avatars : Chỉ seed avatar cho user}
                            {--rooms : Chỉ seed ảnh cho phòng}
                            {--force : Ghi đè ảnh đã tồn tại}';

    protected $description = 'Tải ảnh mẫu vào storage và cập nhật database (avatar user, ảnh phòng). Mỗi user/phòng nhận ảnh khác nhau.';

    private const AVATAR_SEED_BASE = 1000;

    /** Fallback URLs khi picsum không tải được */
    private array $avatarFallbacks = [
        'https://randomuser.me/api/portraits/men/1.jpg',
        'https://randomuser.me/api/portraits/women/1.jpg',
        'https://randomuser.me/api/portraits/men/2.jpg',
        'https://randomuser.me/api/portraits/women/2.jpg',
        'https://randomuser.me/api/portraits/men/3.jpg',
        'https://randomuser.me/api/portraits/women/3.jpg',
        'https://randomuser.me/api/portraits/men/4.jpg',
        'https://randomuser.me/api/portraits/women/4.jpg',
        'https://randomuser.me/api/portraits/men/5.jpg',
        'https://randomuser.me/api/portraits/women/5.jpg',
    ];

    public function handle(): int
    {
        $avatarsOnly = $this->option('avatars');
        $roomsOnly = $this->option('rooms');
        $force = $this->option('force');

        if (! $avatarsOnly && ! $roomsOnly) {
            $avatarsOnly = true;
            $roomsOnly = true;
        }

        $this->info('Bắt đầu seed ảnh...');

        if ($avatarsOnly) {
            $this->seedAvatars($force);
        }

        if ($roomsOnly) {
            $this->seedRoomImages($force);
        }

        $this->info('Hoàn tất.');
        return 0;
    }

    private function seedAvatars(bool $force): void
    {
        $this->info('--- Seed avatar cho user (admin + guest) ---');

        Storage::disk('public')->makeDirectory('avatars');

        $users = User::all();
        $count = 0;

        foreach ($users as $user) {
            $path = 'avatars/user_' . $user->id . '.jpg';

            if (! $force && $user->avatar_url && ! str_starts_with($user->avatar_url, 'http')) {
                $this->line("  User #{$user->id} ({$user->email}) đã có avatar, bỏ qua.");
                continue;
            }

            $seed = self::AVATAR_SEED_BASE + $user->id;
            $url = "https://picsum.photos/seed/{$seed}/200/200";
            $content = $this->downloadImage($url);

            if ($content === null) {
                $fallbackUrl = $this->avatarFallbacks[$user->id % count($this->avatarFallbacks)];
                $content = $this->downloadImage($fallbackUrl);
            }
            if ($content === null) {
                $this->warn("  Không tải được avatar cho user #{$user->id}, tạo placeholder.");
                $content = $this->createPlaceholderImage(200, 200, 'U' . $user->id);
            }

            Storage::disk('public')->put($path, $content);
            $user->update(['avatar_url' => $path]);
            $count++;
            $role = $user->isAdmin() ? ' [admin]' : '';
            $this->line("  User #{$user->id} ({$user->email}){$role}: {$path}");
        }

        $this->info("Đã cập nhật {$count} avatar.");
    }

    private function seedRoomImages(bool $force): void
    {
        $this->info('--- Seed ảnh phòng khách sạn ---');

        RoomImageStorage::ensureDirectories();

        $rooms = Room::all();
        $count = 0;
        $urls = config('room_images.sample_urls', []);
        $totalUrls = count($urls);
        if ($totalUrls === 0) {
            $this->warn('  Không có sample_urls trong config/room_images.php.');

            return;
        }

        foreach ($rooms as $room) {
            if (! $force && $room->image && ! str_starts_with($room->image, 'http')) {
                $this->line("  Phòng #{$room->id} ({$room->name}) đã có ảnh local, bỏ qua.");
                continue;
            }

            $savedPaths = [];
            $imagesPerRoom = 4;
            $offset = ($room->id * $imagesPerRoom) % $totalUrls;

            for ($i = 1; $i <= $imagesPerRoom; $i++) {
                $path = RoomImageStorage::galleryPathForRoom($room->id, $i);
                $url = $urls[($offset + $i - 1) % $totalUrls];
                $content = $this->downloadImage($url);

                if ($content === null) {
                    $this->warn("  Không tải được ảnh phòng #{$room->id} (ảnh " . $i . "), tạo placeholder.");
                    $content = $this->createPlaceholderImage(800, 600, 'R' . $room->id . '-' . $i);
                }

                Storage::disk('public')->put($path, $content);
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
            $this->line("  Phòng #{$room->id} ({$room->name}): " . count($savedPaths) . ' ảnh');
        }

        $this->info("Đã cập nhật ảnh cho {$count} phòng.");
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

        return $content ?: $this->createMinimalJpeg();
    }

    private function createMinimalJpeg(): string
    {
        $img = imagecreatetruecolor(1, 1);
        $bg = imagecolorallocate($img, 200, 200, 200);
        imagefill($img, 0, 0, $bg);
        ob_start();
        imagejpeg($img, null, 85);
        $content = ob_get_clean();
        return $content ?? '';
    }
}
