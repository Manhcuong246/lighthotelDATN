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
    'description',
    'image',
    'status',
    'is_non_refundable',
];

    protected $casts = [
        'is_non_refundable' => 'boolean',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Ảnh bìa cho danh sách: ưu tiên ảnh phòng, sau đó ảnh loại phòng, cuối cùng placeholder.
     */
    public function resolveCoverImageUrl(string $placeholderDataUri): string
    {
        $this->loadMissing(['rooms.images']);

        foreach ($this->rooms as $room) {
            foreach ($room->getDisplayImageUrls() as $url) {
                if ($url !== '') {
                    return $url;
                }
            }
        }

        if ($this->image) {
            $url = Room::resolveImageUrl($this->image);
            if ($url) {
                return $url;
            }
        }

        return $placeholderDataUri;
    }
}
