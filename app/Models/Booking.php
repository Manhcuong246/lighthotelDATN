<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\RefundLog;
use Illuminate\Support\Facades\URL;

class Booking extends Model
{
    protected $table = 'bookings';

    public $timestamps = true;

    protected $attributes = [
        'status' => 'pending',
        'payment_status' => 'pending',
        'discount_amount' => 0,
    ];

    protected $fillable = [
        'user_id',
        'room_id',
        'check_in',
        'check_out',
        'check_in_date',
        'check_out_date',
        'actual_check_in',
        'actual_check_out',
        'guests',
        'adults',
        'children',
        'total_price',
        'status',
        'payment_status',
        'payment_method',
        'placed_via',
        'coupon_code',
        'discount_amount',
        'cancellation_reason',
        'cancel_reason',
        'cancelled_at',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'check_in_date' => 'datetime',
        'check_out_date' => 'datetime',
        'actual_check_in' => 'datetime',
        'actual_check_out' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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

    public function refundLogs()
    {
        return $this->hasMany(RefundLog::class);
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

    public function surcharges()
    {
        return $this->hasMany(BookingSurcharge::class);
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

    /** Đặt từ website khách (kể cả không đăng nhập — tài khoản shadow theo email). */
    public const PLACED_VIA_CUSTOMER_WEB = 'customer_web';

    /** Admin đặt hộ khách. */
    public const PLACED_VIA_ADMIN = 'admin';

    /**
     * Link xem chi tiết đơn cho khách không đăng nhập (kèm chữ ký URL).
     */
    public function signedPublicShowUrl(?int $ttlDays = null): string
    {
        $days = $ttlDays ?? max(1, (int) config('booking.signed_booking_show_ttl_days', 90));

        return URL::signedRoute(
            'bookings.show',
            ['booking' => $this->id],
            now()->addDays($days)
        );
    }

    public function signedPublicCancelUrl(?int $ttlDays = null): string
    {
        $days = $ttlDays ?? max(1, (int) config('booking.signed_booking_show_ttl_days', 90));

        return URL::signedRoute(
            'bookings.cancel',
            ['booking' => $this->id],
            now()->addDays($days)
        );
    }

    public function signedPublicPolicyUrl(?int $ttlDays = null): string
    {
        $days = $ttlDays ?? max(1, (int) config('booking.signed_booking_show_ttl_days', 90));

        return URL::signedRoute(
            'bookings.policy',
            ['booking' => $this->id],
            now()->addDays($days)
        );
    }
}


