<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    protected $fillable = [
        'name',
        'capacity',
        'adult_capacity',
        'child_capacity',
        'beds',
        'baths',
        'price',
        'adult_price',
        'child_price',
        'adult_surcharge_rate',
        'child_surcharge_rate',
        'description',
        'image',
        'status',
    ];

    protected $casts = [
        'adult_surcharge_rate' => 'float',
        'child_surcharge_rate' => 'float',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Resolve image URL — delegates to Room::resolveImageUrl() for consistency.
     */
    public static function resolveImageUrl(?string $path): ?string
    {
        return Room::resolveImageUrl($path);
    }

    /**
     * Accessor: $roomType->image_url
     */
    public function getImageUrlAttribute(): ?string
    {
        return self::resolveImageUrl($this->image);
    }
}
