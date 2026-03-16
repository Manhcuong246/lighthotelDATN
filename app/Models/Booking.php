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
        'room_id',
        'room_type_id',
        'preferred_room_number',
        'check_in',
        'check_out',
        'actual_check_in',
        'actual_check_out',
        'guests',
        'total_price',
        'deposit_amount',
        'payment_request_sent_at',
        'deposit_paid_at',
        'payment_method',
        'payment_transaction_id',
        'status',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'actual_check_in' => 'datetime',
        'actual_check_out' => 'datetime',
        'payment_request_sent_at' => 'datetime',
        'deposit_paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Constants cho booking statuses
    const STATUS_PENDING = 'pending'; // Chờ xác nhận
    const STATUS_AWAITING_PAYMENT = 'awaiting_payment'; // Yêu cầu thanh toán
    const STATUS_CONFIRMED = 'confirmed'; // Đã thanh toán/Đặt thành công
    const STATUS_CANCELLED = 'cancelled'; // Đã hủy
    const STATUS_COMPLETED = 'completed'; // Đã hoàn thành stay

    /**
     * Get available status options
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Chờ xác nhận',
            self::STATUS_AWAITING_PAYMENT => 'Yêu cầu thanh toán',
            self::STATUS_CONFIRMED => 'Đã đặt thành công',
            self::STATUS_CANCELLED => 'Đã hủy',
            self::STATUS_COMPLETED => 'Hoàn thành',
        ];
    }

    /**
     * Check if booking needs deposit payment (30%)
     */
    public function requiresDeposit(): bool
    {
        return $this->status === self::STATUS_AWAITING_PAYMENT && !$this->deposit_paid_at;
    }

    /**
     * Get deposit amount (30% of total)
     */
    public function getDepositAmount(): float
    {
        if ($this->deposit_amount) {
            return (float) $this->deposit_amount;
        }
        
        // Tính 30% tổng tiền nếu chưa có
        return round((float) $this->total_price * 0.3, 2);
    }

    /**
     * Check if deposit is paid
     */
    public function isDepositPaid(): bool
    {
        return !is_null($this->deposit_paid_at);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function logs()
    {
        return $this->hasMany(BookingLog::class);
    }

    public function bookedDates()
    {
        return $this->hasMany(RoomBookedDate::class);
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

    /**
     * Auto-assign phòng trống cho booking
     * Dựa trên room_type_id và khoảng thời gian check-in/check-out
     */
    public static function assignAvailableRoom($roomTypeId, $checkIn, $checkOut, $preferredRoomNumber = null)
    {
        // Lấy tất cả phòng thuộc loại này
        $rooms = Room::where('room_type_id', $roomTypeId)
            ->where('status', 'available')
            ->get();

        // Nếu có preferred_room_number, ưu tiên phòng đó trước
        if ($preferredRoomNumber) {
            $preferredRoom = $rooms->firstWhere('name', $preferredRoomNumber);
            if ($preferredRoom && self::isRoomAvailable($preferredRoom->id, $checkIn, $checkOut)) {
                return $preferredRoom;
            }
        }

        // Tìm phòng trống đầu tiên
        foreach ($rooms as $room) {
            if (self::isRoomAvailable($room->id, $checkIn, $checkOut)) {
                return $room;
            }
        }

        return null; // Không còn phòng trống
    }

    /**
     * Kiểm tra xem phòng có available trong khoảng thời gian không
     */
    public static function isRoomAvailable($roomId, $checkIn, $checkOut)
    {
        // Check xem phòng đã có booking nào trong khoảng thời gian chưa
        $existingBooking = Booking::where('room_id', $roomId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function($query) use ($checkIn, $checkOut) {
                // Overlapping date check
                $query->whereBetween('check_in', [$checkIn, $checkOut])
                      ->orWhereBetween('check_out', [$checkIn, $checkOut])
                      ->orWhere(function($q) use ($checkIn, $checkOut) {
                          $q->where('check_in', '<=', $checkIn)
                            ->where('check_out', '>=', $checkOut);
                      });
            })
            ->first();

        return !$existingBooking; // Nếu không có booking nào → available
    }
}


