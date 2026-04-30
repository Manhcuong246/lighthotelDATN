<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingRoom extends Model
{
    protected $table = 'booking_rooms';

    protected $fillable = [
        'booking_id',
        'room_id',
        'adults',
        'children_0_5',
        'children_6_11',
        'price_per_night',
        'nights',
        'subtotal',
    ];

    protected $casts = [
        'price_per_night' => 'float',
        'subtotal'        => 'float',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }
}
