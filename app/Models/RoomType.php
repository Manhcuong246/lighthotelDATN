<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'capacity',
        'standard_capacity',
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
        'standard_capacity' => 'integer',
        'capacity' => 'integer',
        'adult_surcharge_rate' => 'float',
        'child_surcharge_rate' => 'float',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    /** Dịch vụ đi kèm có sẵn (danh mục dịch vụ). */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'room_type_service');
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
