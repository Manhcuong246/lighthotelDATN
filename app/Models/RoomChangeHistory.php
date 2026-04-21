<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomChangeHistory extends Model
{
    protected $table = 'room_change_history';

    protected $fillable = [
        'booking_id',
        'from_room_id',
        'to_room_id',
        'damage_report_id',
        'reason',
        'changed_by',
        'changed_at',
        'old_price_per_night',
        'new_price_per_night',
        'price_difference',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'old_price_per_night' => 'decimal:2',
        'new_price_per_night' => 'decimal:2',
        'price_difference' => 'decimal:2',
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

    public function damageReport(): BelongsTo
    {
        return $this->belongsTo(DamageReport::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
