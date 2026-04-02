<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Booking extends Model
{
    protected $table = 'bookings';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'check_in',
        'check_out',
        'actual_check_in',
        'actual_check_out',
        'guests',
        'adults',
        'children',
        'total_price',
        'status',
        'cancellation_reason',
        'cancellation_requested_at',
        'coupon_code',
        'discount_amount',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'actual_check_in' => 'datetime',
        'actual_check_out' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'cancellation_requested_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLATION_PENDING = 'cancellation_pending';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_COMPLETED = 'completed';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Quan hệ mới: 1 booking có nhiều booking_rooms
     */
    public function bookingRooms()
    {
        return $this->hasMany(BookingRoom::class);
    }

    /**
     * Shortcut: danh sách Room qua booking_rooms
     */
    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'booking_rooms')
                    ->withPivot('price_per_night', 'nights', 'subtotal', 'adults', 'children_0_5', 'children_6_11')
                    ->withTimestamps();
    }

    /**
     * Giữ lại để tương thích với code admin cũ (dùng $booking->room)
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function logs()
    {
        return $this->hasMany(BookingLog::class);
    }

    public function bookedDates()
    {
        return $this->hasMany(RoomBookedDate::class);
    }

    public function bookingServices()
    {
        return $this->hasMany(BookingService::class);
    }

    public function refundRequest()
    {
        return $this->hasOne(RefundRequest::class);
    }

    public function isCheckinAllowed(): bool
    {
        return $this->status === 'confirmed'
            && is_null($this->actual_check_in)
            && Carbon::today()->gte($this->check_in);
    }

    public function isCheckoutAllowed(): bool
    {
        return $this->status === 'confirmed'
            && !is_null($this->actual_check_in)
            && is_null($this->actual_check_out)
            && Carbon::today()->gte($this->check_out);
    }

    public function releaseBookedDates(): void
    {
        RoomBookedDate::where('booking_id', $this->id)->delete();
    }

    /**
     * Số khách để form admin / validate: cột guests hoặc tổng adults từ booking_rooms (đơn đa phòng thường để guests null).
     */
    public function resolvedGuestCount(): int
    {
        if ($this->guests !== null) {
            return max(1, (int) $this->guests);
        }

        $this->loadMissing('bookingRooms');

        return max(1, (int) $this->bookingRooms->sum('adults'));
    }

    /**
     * Tên phòng để hiển thị admin/CSV: ưu tiên quan hệ booking_rooms, fallback room_id cũ.
     */
    public function roomNamesLabel(): string
    {
        $this->loadMissing(['rooms', 'room']);
        if ($this->rooms->isNotEmpty()) {
            return $this->rooms->pluck('name')->filter()->values()->implode(', ');
        }

        return $this->room?->name ?: '—';
    }
}


