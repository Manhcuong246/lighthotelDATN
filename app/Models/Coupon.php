<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $table = 'coupons';

    public $timestamps = false;

    protected $fillable = [
        'code',
        'discount_percent',
        'expired_at',
        'is_active',
    ];

    protected $casts = [
        'expired_at' => 'date',
        'is_active' => 'boolean',
    ];
}


