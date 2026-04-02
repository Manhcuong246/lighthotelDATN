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
        'bank_id',
        'bank_account',
        'bank_account_name',
        'latitude',
        'longitude',
        'rating_avg',
    ];
}


