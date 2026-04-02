<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundRequest extends Model
{
    protected $table = 'refund_requests';

    protected $fillable = [
        'booking_id',
        'user_id',
        'account_name',
        'account_number',
        'bank_name',
        'qr_image',
        'refund_percentage',
        'refund_amount',
        'note',
        'admin_note',
        'refund_proof_image',
        'status',
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
