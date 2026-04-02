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
}
