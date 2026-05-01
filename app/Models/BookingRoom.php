<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingRoom extends Model
{
    protected $table = 'booking_rooms';

    protected $fillable = [
        'booking_id',
        'room_type_id',
        'room_id',
        'adults',
        'children_0_5',
        'children_6_11',
        'price_per_night',
        'nights',
        'subtotal',
    ];

    protected $casts = [
        'price_per_night' => 'float',
        'subtotal'        => 'float',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * Số dòng đặt theo loại phòng chưa gán phòng vật lý, trùng khoảng ngày với check-in/check-out.
     */
    public static function unassignedCountForRoomTypeBetween(int $roomTypeId, string $checkIn, string $checkOut): int
    {
        return static::query()
            ->where('room_type_id', $roomTypeId)
            ->whereNull('room_id')
            ->whereHas('booking', function ($q) use ($checkIn, $checkOut) {
                $q->whereNotIn('status', ['cancelled', 'refunded'])
                    ->whereDate('check_in', '<', $checkOut)
                    ->whereDate('check_out', '>', $checkIn);
            })
            ->count();
    }

    /** Nhãn hiển thị cho khách (chưa có số phòng cụ thể). */
    public function guestFacingLine(): string
    {
        $this->loadMissing('room.roomType', 'roomType');
        if ($this->room) {
            return $this->room->displayLabel();
        }

        return $this->roomType?->name
            ? $this->roomType->name.' (số phòng do lễ tân bố trí)'
            : 'Phòng (chưa gán số)';
    }
}
