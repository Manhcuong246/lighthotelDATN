<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingFinancialAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'booking_financial_audit_logs';

    protected $fillable = [
        'booking_id',
        'user_id',
        'action',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
