<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;

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

    protected static function booted(): void
    {
        static::deleting(function (Coupon $coupon): void {
            if ($coupon->isForceDeleting()) {
                return;
            }
            $code = (string) $coupon->code;
            if ($code !== '' && ! str_starts_with($code, '__D')) {
                $prefix = '__D' . $coupon->id . '_';
                $coupon->code = $prefix . substr($code, 0, max(1, 50 - strlen($prefix)));
            }
        });
    }
}


