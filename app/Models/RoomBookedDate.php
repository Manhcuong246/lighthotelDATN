<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomBookedDate extends Model
{
    protected $table = 'room_booked_dates';

    public $timestamps = false;

    protected $fillable = [
        'room_id',
        'booked_date',
        'booking_id',
    ];

    protected $casts = [
        'booked_date' => 'date',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}


