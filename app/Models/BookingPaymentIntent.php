<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPaymentIntent extends Model
{
    public const TXN_REF_PREFIX = 'LHINT';

    protected $table = 'booking_payment_intents';

    protected $fillable = [
        'payload',
        'amount_vnd',
        'expires_at',
        'booking_id',
        'completed_at',
        'abandoned_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'abandoned_at' => 'datetime',
            'amount_vnd' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function vnPayTxnRef(): string
    {
        return self::TXN_REF_PREFIX.$this->id;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
