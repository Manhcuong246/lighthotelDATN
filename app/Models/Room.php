<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        return $this->hasMany(Image::class);
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
     * Resolve image URL - handles storage path, full URL, or returns null for fallback.
     */
    public static function resolveImageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return asset('storage/' . $path);
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
}


