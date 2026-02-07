<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $table = 'coupons';

    public $timestamps = true;

    protected $fillable = [
        'code',
        'discount_percent',
        'expired_at',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'expired_at' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}


