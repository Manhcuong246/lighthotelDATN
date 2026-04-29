<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class BookingLog extends Model
{
    protected $table = 'booking_logs';

    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'user_id',
        'old_status',
        'new_status',
        'notes',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}


