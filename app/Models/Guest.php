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
        'room_id',      // phòng cụ thể (gán khi check-in)
        'room_index',   // index của phòng trong booking (0, 1, 2...)
        'room_type',
        'name',
        'cccd',
        'type',         // adult, child
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
     * Get the specific room assigned to this guest (check-in)
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Check if guest has been assigned to a specific room
     */
    public function hasAssignedRoom(): bool
    {
        return !is_null($this->room_id);
    }

    /**
     * Get room display info: "101 (Standard)" or "Phòng 1" if not assigned
     */
    public function getRoomDisplayAttribute(): string
    {
        if ($this->room) {
            $roomNumber = $this->room->room_number ?? $this->room->name ?? '#' . $this->room->id;
            $roomType = $this->room->roomType?->name ?? $this->room_type ?? 'Phòng';
            return "{$roomNumber} ({$roomType})";
        }

        // Fallback: chưa gán phòng
        return 'Chưa gán phòng (' . ($this->room_type ?? 'Phòng ' . ($this->room_index + 1)) . ')';
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
