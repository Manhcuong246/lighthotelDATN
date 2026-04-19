<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingSurcharge extends Model
{
    protected $fillable = [
        'booking_id',
        'service_id',
        'reason',
        'quantity',
        'amount',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'amount' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
