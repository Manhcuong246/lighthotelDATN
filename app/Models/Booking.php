<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = 'bookings';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'room_id',
        'check_in',
        'check_out',
        'actual_check_in',
        'actual_check_out',
        'guests',
        'total_price',
        'status',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'actual_check_in' => 'datetime',
        'actual_check_out' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function logs()
    {
        return $this->hasMany(BookingLog::class);
    }

    public function bookedDates()
    {
        return $this->hasMany(RoomBookedDate::class);
    }
}


