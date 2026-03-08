<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
  protected $fillable = [
    'name',
    'capacity',
    'price',
    'description',
    'status'
];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
