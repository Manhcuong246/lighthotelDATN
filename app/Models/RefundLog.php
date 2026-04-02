<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'refund_amount',
        'refund_type',
        'reason',
        'processed_by',
        'refunded_at',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'refunded_at' => 'datetime',
    ];

    /**
     * Get the booking associated with this refund log.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the user who processed this refund.
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get formatted refund amount.
     */
    public function getFormattedRefundAmountAttribute()
    {
        return number_format($this->refund_amount, 0, ',', '.') . ' ₫';
    }

    /**
     * Get refund type label in Vietnamese.
     */
    public function getRefundTypeLabelAttribute()
    {
        return match($this->refund_type) {
            'full' => 'Hoàn tiền đầy đủ',
            'partial' => 'Hoàn tiền một phần',
            'none' => 'Không hoàn tiền',
            default => 'Không xác định',
        };
    }
}
