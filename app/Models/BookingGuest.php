<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingGuest extends Model
{
    protected $table = 'booking_guests';

    protected $fillable = [
        'booking_id',
        'booking_room_id',
        'name',
        'cccd',
        'type',
        'status',
        'is_representative',
        'checkin_status',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingRoom()
    {
        return $this->belongsTo(BookingRoom::class);
    }

    /**
     * Lấy phòng của khách (thông qua booking_room)
     */
    public function room()
    {
        return $this->bookingRoom?->room();
    }

    /**
     * Kiểm tra khách đã được gán phòng chưa
     */
    public function hasAssignedRoom(): bool
    {
        return !is_null($this->booking_room_id);
    }
}
