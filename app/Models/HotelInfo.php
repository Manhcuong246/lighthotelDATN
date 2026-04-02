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
        'default_check_in_time',
        'cancel_free_hours',
        'cancel_mid_hours_low',
        'cancel_penalty_mid_percent',
        'cancel_penalty_short_percent',
        'cancel_require_admin_when_penalty',
    ];
}


