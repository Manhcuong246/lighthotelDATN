<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingLog extends Model
{
    protected $table = 'booking_logs';

    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'old_status',
        'new_status',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}


