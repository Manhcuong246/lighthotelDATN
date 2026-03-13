<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelInfo extends Model
{
    protected $table = 'hotel_info';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'address',
        'phone',
        'email',
        'latitude',
        'longitude',
        'rating_avg',
    ];
}


