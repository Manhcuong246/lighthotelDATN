<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomPrice extends Model
{
    protected $table = 'room_prices';

    public $timestamps = false;

    protected $fillable = [
        'room_id',
        'price',
        'start_date',
        'end_date',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}


