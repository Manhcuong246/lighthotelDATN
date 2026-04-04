<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingSurcharge extends Model
{
    protected $fillable = ['booking_id', 'reason', 'amount'];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
