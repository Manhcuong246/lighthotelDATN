<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
     * Scope booking phục vụ đếm "giữ chỗ" (logic chung một lần, tránh lệch batch vs đơn lẻ).
     */
    public static function applyUnassignedOccupancyBookingOverlap(Builder $q, string $checkIn, string $checkOut): void
    {
        $q->whereNotIn('status', ['cancelled', 'cancel_requested', 'completed'])
            ->whereDate('check_in', '<', $checkOut)
            ->whereDate('check_out', '>', $checkIn)
            ->where(static function (Builder $slot): void {
                // Giữ chỗ khi đã có thanh toán / cam kết đơn hợp lệ (tránh DDOS đặt chỗ chưa trả tiền trên web).
                $slot->whereIn('payment_status', ['paid', 'partial'])
                    ->orWhere(static function (Builder $adm): void {
                        $adm->where('placed_via', Booking::PLACED_VIA_ADMIN)
                            ->whereIn('status', ['confirmed', 'checked_in']);
                    });
            });
    }

    /**
     * @return array<int, int> room_type_id => số dòng chưa gán phòng vật lý
     */
    public static function unassignedCountsForRoomTypesBetween(array $roomTypeIds, string $checkIn, string $checkOut): array
    {
        $roomTypeIds = array_values(array_unique(array_values(array_filter(array_map('intval', $roomTypeIds)))));
        if ($roomTypeIds === []) {
            return [];
        }

        /** @var \Illuminate\Support\Collection<int|string,int|string> */
        $plucked = static::query()
            ->selectRaw('booking_rooms.room_type_id, COUNT(*) as unassigned_aggregate')
            ->whereNull('room_id')
            ->whereIn('room_type_id', $roomTypeIds)
            ->whereHas('booking', function ($bq) use ($checkIn, $checkOut): void {
                static::applyUnassignedOccupancyBookingOverlap($bq, $checkIn, $checkOut);
            })
            ->groupBy('booking_rooms.room_type_id')
            ->pluck('unassigned_aggregate', 'room_type_id');

        return $plucked->mapWithKeys(fn ($c, $id): array => [(int) $id => (int) $c])->all();
    }

    /**
     * Số dòng đặt theo loại phòng chưa gán phòng vật lý, trùng khoảng ngày với check-in/check-out.
     */
    public static function unassignedCountForRoomTypeBetween(int $roomTypeId, string $checkIn, string $checkOut): int
    {
        return (int) (static::unassignedCountsForRoomTypesBetween([$roomTypeId], $checkIn, $checkOut)[$roomTypeId] ?? 0);
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
