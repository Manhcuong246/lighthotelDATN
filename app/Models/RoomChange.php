<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomChange extends Model
{
    protected $table = 'room_changes';

    protected $fillable = [
        'booking_id',
        'from_room_id',
        'to_room_id',
        'price_diff',
        'surcharge_amount',
        'reason',
        'changed_by',
    ];

    protected $casts = [
        'price_diff' => 'decimal:2',
        'surcharge_amount' => 'decimal:2',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function fromRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'from_room_id');
    }

    public function toRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'to_room_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Tính tổng chênh lệch (giá phòng + phụ thu)
     */
    public function getTotalDiffAttribute(): float
    {
        return (float) ($this->price_diff + $this->surcharge_amount);
    }

    /**
     * Mapping for existing view attributes
     */
    public function getPriceDifferenceAttribute()
    {
        return $this->price_diff;
    }

    public function getChangedAtAttribute()
    {
        return $this->created_at;
    }

    public function getChangeTypeLabelAttribute()
    {
        if ($this->price_diff > 0) return 'Nâng hạng';
        if ($this->price_diff < 0) return 'Hạ hạng';
        return 'Cùng hạng';
    }

    public function getChangeTypeBadgeAttribute()
    {
        if ($this->price_diff > 0) return 'bg-primary';
        if ($this->price_diff < 0) return 'bg-success';
        return 'bg-secondary';
    }
}
