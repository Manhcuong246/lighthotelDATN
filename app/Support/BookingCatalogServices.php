<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Danh mục dịch vụ có thể gán vào đơn theo loại phòng (bảng room_type_service).
 * Fallback toàn danh mục khi chưa suy ra được loại phòng hoặc chưa cấu hình pivot.
 */
final class BookingCatalogServices
{
    /**
     * @return list<int>
     */
    public static function resolveRoomTypeIds(Booking $booking): array
    {
        $booking->loadMissing(['bookingRooms.room.roomType', 'bookingRooms.roomType', 'room.roomType']);

        $ids = collect();

        foreach ($booking->bookingRooms as $br) {
            $tid = $br->room_type_id
                ? (int) $br->room_type_id
                : ($br->room?->room_type_id ? (int) $br->room->room_type_id : null);
            if ($tid !== null && $tid > 0) {
                $ids->push($tid);
            }
        }

        if ($ids->isEmpty() && $booking->room_id) {
            $tid = $booking->room?->room_type_id;
            if ($tid) {
                $ids->push((int) $tid);
            }
        }

        return $ids->unique()->values()->all();
    }

    /**
     * @return Collection<int, Service>
     */
    public static function forBooking(Booking $booking): Collection
    {
        $query = Service::query()->orderBy('name');

        $roomTypeIds = self::resolveRoomTypeIds($booking);

        if ($roomTypeIds === [] || ! Schema::hasTable('room_type_service')) {
            return $query->get();
        }

        $serviceIds = DB::table('room_type_service')
            ->whereIn('room_type_id', $roomTypeIds)
            ->distinct()
            ->pluck('service_id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        if ($serviceIds === []) {
            return $query->get();
        }

        return $query->whereIn('id', $serviceIds)->get();
    }

    /**
     * @return list<int>
     */
    public static function eligibleIds(Booking $booking): array
    {
        return self::forBooking($booking)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public static function resolveNoticeForBooking(Booking $booking): ?string
    {
        $typeIds = self::resolveRoomTypeIds($booking);
        if ($typeIds === []) {
            return 'Đơn chưa xác định loại phòng — có thể chọn mọi dịch vụ trong danh mục.';
        }

        if (! Schema::hasTable('room_type_service')) {
            return null;
        }

        $cnt = (int) DB::table('room_type_service')
            ->whereIn('room_type_id', $typeIds)
            ->distinct()
            ->count('service_id');

        if ($cnt === 0) {
            return 'Loại phòng trên đơn chưa được gắn dịch vụ — có thể chọn mọi dịch vụ (nên cấu hình tại Loại phòng).';
        }

        return null;
    }
}
