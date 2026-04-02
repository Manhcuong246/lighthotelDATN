<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Room extends Model
{
    protected $table = 'rooms';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'type',
        'base_price',
        'max_guests',
        'beds',
        'baths',
        'area',
        'description',
        'status',
        'room_type_id',
        'room_number',
        'image',
    ];

    public function prices()
    {
        return $this->hasMany(RoomPrice::class);
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'room_amenities');
    }

    public function images()
    {
        return $this->hasMany(Image::class)->orderBy('id');
    }

    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_rooms')
                    ->withPivot('price_per_night', 'nights', 'subtotal')
                    ->withTimestamps();
    }

    public function bookedDates()
    {
        return $this->hasMany(RoomBookedDate::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

   public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * Chuẩn hóa path tương đối trong storage/app/public (vd: rooms/a.jpg).
     */
    public static function normalizePublicStoragePath(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        $path = str_replace('\\', '/', trim($path));
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return null;
        }
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return $path !== '' ? $path : null;
    }

    /**
     * URL hiển thị ảnh — dùng đường dẫn gốc /storage/... để không phụ thuộc APP_URL/cổng.
     */
    public static function resolveImageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        $path = str_replace('\\', '/', trim($path));
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        $rel = self::normalizePublicStoragePath($path);

        return $rel ? asset('storage/'.$rel) : null;
    }

    /**
     * Ảnh đại diện admin: ưu tiên file thật tồn tại trên disk, sau đó URL ngoài, cuối cùng placeholder.
     */
    public function adminThumbnailUrl(): string
    {
        $this->loadMissing(['images', 'roomType']);

        foreach ($this->images as $img) {
            if (! $img->image_url) {
                continue;
            }
            if (str_starts_with($img->image_url, 'http://') || str_starts_with($img->image_url, 'https://')) {
                return $img->image_url;
            }
            $rel = self::normalizePublicStoragePath($img->image_url);
            if ($rel && Storage::disk('public')->exists($rel)) {
                return asset('storage/'.$rel);
            }
        }

        foreach ([$this->image, $this->roomType?->image] as $raw) {
            if (empty($raw)) {
                continue;
            }
            if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
                return $raw;
            }
            $rel = self::normalizePublicStoragePath($raw);
            if ($rel && Storage::disk('public')->exists($rel)) {
                return asset('storage/'.$rel);
            }
        }

        return asset('images/placeholder-room.svg');
    }

    /**
     * Get all displayable image URLs for this room (images, room.image, roomType.image).
     */
    public function getDisplayImageUrls(): array
    {
        $urls = [];
        foreach ($this->images as $img) {
            if ($img->image_url) {
                $url = self::resolveImageUrl($img->image_url);
                if ($url) {
                    $urls[] = $url;
                }
            }
        }
        if (empty($urls) && $this->image) {
            $url = self::resolveImageUrl($this->image);
            if ($url) {
                $urls[] = $url;
            }
        }
        if (empty($urls) && $this->roomType?->image) {
            $url = self::resolveImageUrl($this->roomType->image);
            if ($url) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    /**
     * Ảnh modal: ưu tiên URL có file; nếu không có thì placeholder.
     */
    public function adminDetailImageUrl(): string
    {
        $this->loadMissing(['images', 'roomType']);

        foreach ($this->images as $img) {
            if (! $img->image_url) {
                continue;
            }
            if (str_starts_with($img->image_url, 'http://') || str_starts_with($img->image_url, 'https://')) {
                return $img->image_url;
            }
            $rel = self::normalizePublicStoragePath($img->image_url);
            if ($rel && Storage::disk('public')->exists($rel)) {
                return asset('storage/'.$rel);
            }
        }

        foreach ([$this->image, $this->roomType?->image] as $raw) {
            if (empty($raw)) {
                continue;
            }
            if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
                return $raw;
            }
            $rel = self::normalizePublicStoragePath($raw);
            if ($rel && Storage::disk('public')->exists($rel)) {
                return asset('storage/'.$rel);
            }
        }

        return asset('images/placeholder-room.svg');
    }
}


