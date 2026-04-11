<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingGuest extends Model{
    protected $fillable = [
    'booking_id',
    'name',
];
}