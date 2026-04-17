<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $booking_id
 * @property int $room_index
 * @property string|null $room_type
 * @property string $name
 * @property string|null $cccd
 * @property string $type
 * @property string $checkin_status
 */
class Guest extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'room_index', // để biết khách thuộc phòng nào
        'room_type',
        'name',
        'cccd',
        'type', // adult, child
        'checkin_status', // enum: 'pending', 'checked_in'
    ];

    protected $casts = [
        'checkin_status' => 'string',
    ];

    /**
     * Get the booking that this guest belongs to
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Check if guest is pending check-in
     */
    public function isPending(): bool
    {
        return $this->checkin_status === 'pending';
    }

    /**
     * Check if guest is checked in
     */
    public function isCheckedIn(): bool
    {
        return $this->checkin_status === 'checked_in';
    }

    /**
     * Get masked CCCD for non-admin users
     */
    public function getMaskedCccdAttribute(): string
    {
        if (!$this->cccd) {
            return '';
        }

        // Show first 6 digits, mask the rest
        return substr($this->cccd, 0, 6) . '****';
    }

    /**
     * Get room display name
     */
    public function getRoomDisplayNameAttribute(): string
    {
        if (! empty($this->room_type)) {
            return 'Phòng ' . ucwords(str_replace(['_', '-'], ' ', $this->room_type));
        }

        return 'Phòng ' . ($this->room_index + 1);
    }
}
