<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Đường dẫn lưu ảnh phòng / loại phòng trên disk public (storage/app/public/...).
 */
class RoomImageStorage
{
    public static function baseDir(): string
    {
        return trim((string) config('room_images.directory', 'room_images'), '/');
    }

    public static function roomsDir(): string
    {
        $sub = trim((string) config('room_images.subdirs.rooms', 'rooms'), '/');

        return self::baseDir().'/'.$sub;
    }

    public static function roomTypesDir(): string
    {
        $sub = trim((string) config('room_images.subdirs.room_types', 'room_types'), '/');

        return self::baseDir().'/'.$sub;
    }

    /**
     * Tên file cố định cho gallery phòng (đồng bộ với lệnh tải ảnh).
     */
    public static function galleryPathForRoom(int $roomId, int $index): string
    {
        return self::roomsDir().'/room_'.$roomId.'_'.$index.'.jpg';
    }

    public static function ensureDirectories(): void
    {
        $disk = Storage::disk('public');
        $disk->makeDirectory(self::roomsDir());
        $disk->makeDirectory(self::roomTypesDir());
    }
}
