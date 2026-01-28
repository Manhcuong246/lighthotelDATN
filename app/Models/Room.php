<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'rooms';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'type',
        'base_price',
        'max_guests',
        'beds',
        'baths',
        'area',
        'description',
        'status',
    ];

    public function prices()
    {
        return $this->hasMany(RoomPrice::class);
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'room_amenities');
    }

    public function images()
    {
        return $this->hasMany(Image::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function bookedDates()
    {
        return $this->hasMany(RoomBookedDate::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}


