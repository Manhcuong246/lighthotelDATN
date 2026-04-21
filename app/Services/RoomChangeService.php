<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomBookedDate;
use App\Models\RoomChangeHistory;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service xử lý chức năng đổi phòng
 * 
 * Chức năng:
 * - Kiểm tra tính khả dụng của phòng mới
 * - Thực hiện đổi phòng với transaction an toàn
 * - Ghi lại lịch sử đổi phòng
 * - Cập nhật giá và payment nếu cần
 */
class RoomChangeService
{
    /**
     * Lấy danh sách phòng có thể đổi cho một booking room cụ thể
     *
     * @param Booking $booking
     * @param int $currentRoomId
     * @return array
     */
    public function getAvailableRoomsForChange(Booking $booking, int $currentRoomId): array
    {
        $booking->load('bookingRooms.room');
        
        // Lấy thông tin booking room hiện tại
        $bookingRoom = $booking->bookingRooms->firstWhere('room_id', $currentRoomId);
        if (!$bookingRoom) {
            return [];
        }

        $currentRoom = $bookingRoom->room;
        $checkIn = Carbon::parse($booking->check_in);
        $checkOut = Carbon::parse($booking->check_out);
        
        // Lấy danh sách phòng đã được đặt trong khoảng thởi gian này (trừ booking hiện tại)
        $bookedRoomIds = RoomBookedDate::whereBetween('booked_date', [$checkIn->toDateString(), $checkOut->copy()->subDay()->toDateString()])
            ->whereHas('booking', function ($q) use ($booking) {
                $q->where('id', '!=', $booking->id)
                  ->whereIn('status', ['pending', 'confirmed', 'checked_in']);
            })
            ->distinct()
            ->pluck('room_id')
            ->toArray();

        // Query các phòng khả dụng
        $query = Room::with('roomType')
            ->where('id', '!=', $currentRoomId)
            ->whereNotIn('id', $bookedRoomIds);

        // Ưu tiên cùng loại phòng
        if ($currentRoom && $currentRoom->room_type_id) {
            $query->orderByRaw('CASE WHEN room_type_id = ? THEN 0 ELSE 1 END', [$currentRoom->room_type_id]);
        }

        $rooms = $query->get();

        return $rooms->map(function ($room) use ($currentRoom) {
            $isSameType = $currentRoom && $room->room_type_id === $currentRoom->room_type_id;
            $priceDiff = $room->catalogueBasePrice() - ($currentRoom ? $currentRoom->catalogueBasePrice() : 0);
            
            return [
                'id' => $room->id,
                'name' => $room->name,
                'room_number' => $room->room_number ?? $room->name,
                'room_type' => $room->roomType,
                'base_price' => $room->catalogueBasePrice(),
                'max_guests' => $room->max_guests,
                'is_same_type' => $isSameType,
                'price_difference' => $priceDiff,
                'status' => $room->status,
                'is_available_now' => $room->status === 'available',
            ];
        })->toArray();
    }

    /**
     * Kiểm tra xem phòng mới có khả dụng không
     *
     * @param int $roomId
     * @param string $checkIn
     * @param string $checkOut
     * @param int|null $excludeBookingId
     * @return bool
     */
    public function isRoomAvailable(int $roomId, string $checkIn, string $checkOut, ?int $excludeBookingId = null): bool
    {
        $query = RoomBookedDate::where('room_id', $roomId)
            ->whereBetween('booked_date', [
                Carbon::parse($checkIn)->toDateString(),
                Carbon::parse($checkOut)->copy()->subDay()->toDateString()
            ])
            ->whereHas('booking', function ($q) {
                $q->whereIn('status', ['pending', 'confirmed', 'checked_in']);
            });

        if ($excludeBookingId) {
            $query->whereHas('booking', function ($q) use ($excludeBookingId) {
                $q->where('id', '!=', $excludeBookingId);
            });
        }

        return $query->doesntExist();
    }

    /**
     * Thực hiện đổi phòng
     *
     * @param Booking $booking
     * @param int $oldRoomId
     * @param int $newRoomId
     * @param string|null $reason
     * @param int|null $changedBy
     * @return array
     * @throws \Exception
     */
    public function changeRoom(
        Booking $booking, 
        int $oldRoomId, 
        int $newRoomId, 
        ?string $reason = null, 
        ?int $changedBy = null
    ): array {
        return DB::transaction(function () use ($booking, $oldRoomId, $newRoomId, $reason, $changedBy) {
            // 1. Kiểm tra phòng mới có khả dụng không
            if (!$this->isRoomAvailable($newRoomId, $booking->check_in, $booking->check_out, $booking->id)) {
                throw new \Exception('Phòng mới đã được đặt trong khoảng thởi gian này');
            }

            // 2. Lấy thông tin
            $newRoom = Room::with('roomType')->findOrFail($newRoomId);
            $bookingRoom = BookingRoom::where('booking_id', $booking->id)
                ->where('room_id', $oldRoomId)
                ->firstOrFail();

            $oldRoom = Room::find($oldRoomId);
            $nights = $bookingRoom->nights;
            $oldPricePerNight = $bookingRoom->price_per_night;

            // 3. Tính toán giá mới
            $newPricePerNight = $this->calculateNewPrice($booking, $newRoom, $oldPricePerNight);
            $newSubtotal = $newPricePerNight * $nights;
            $oldSubtotal = $bookingRoom->subtotal;
            $priceDifference = $newSubtotal - $oldSubtotal;

            // 4. Cập nhật booking_rooms
            $bookingRoom->update([
                'room_id' => $newRoomId,
                'price_per_night' => $newPricePerNight,
                'subtotal' => $newSubtotal,
            ]);

            // 5. Cập nhật room_booked_dates
            $this->updateRoomBookedDates($booking, $oldRoomId, $newRoomId);

            // 6. Cập nhật trạng thái phòng nếu đang trong thởi gian ở
            $this->updateRoomStatuses($oldRoomId, $newRoomId, $booking);

            // 7. Tính lại tổng tiền booking
            $newTotalPrice = $this->recalculateBookingTotal($booking);

            // 8. Ghi lịch sử đổi phòng
            $history = $this->createChangeHistory(
                $booking->id,
                $oldRoomId,
                $newRoomId,
                $reason,
                $changedBy,
                $oldPricePerNight,
                $newPricePerNight,
                $priceDifference
            );

            // 9. Cập nhật payment nếu cần
            $paymentUpdate = $this->updatePaymentIfNeeded($booking, $newTotalPrice);

            // 10. Ghi log
            Log::info('Room change completed', [
                'booking_id' => $booking->id,
                'from_room_id' => $oldRoomId,
                'to_room_id' => $newRoomId,
                'price_difference' => $priceDifference,
                'changed_by' => $changedBy,
            ]);

            return [
                'success' => true,
                'history_id' => $history->id,
                'old_room' => $oldRoom?->name ?? 'Unknown',
                'new_room' => $newRoom->name,
                'old_price' => $oldSubtotal,
                'new_price' => $newSubtotal,
                'price_difference' => $priceDifference,
                'payment_updated' => $paymentUpdate,
            ];
        });
    }

    /**
     * Tính giá mới cho phòng
     */
    private function calculateNewPrice(Booking $booking, Room $newRoom, float $oldPricePerNight): float
    {
        // Nếu cùng loại phòng, giữ nguyên giá cũ
        $oldRoom = $booking->bookingRooms->firstWhere('room_id', $booking->bookingRooms->first()->room_id);
        if ($oldRoom && $oldRoom->room && $oldRoom->room->room_type_id === $newRoom->room_type_id) {
            return $oldPricePerNight;
        }

        // Nếu khác loại phòng, lấy giá catalogue
        return $newRoom->catalogueBasePrice();
    }

    /**
     * Cập nhật room_booked_dates
     */
    private function updateRoomBookedDates(Booking $booking, int $oldRoomId, int $newRoomId): void
    {
        // Xóa các ngày đã đặt của phòng cũ
        RoomBookedDate::where('booking_id', $booking->id)
            ->where('room_id', $oldRoomId)
            ->delete();

        // Thêm các ngày cho phòng mới
        $period = CarbonPeriod::create($booking->check_in, $booking->check_out->copy()->subDay());
        $days = [];
        
        foreach ($period as $date) {
            $days[] = [
                'room_id' => $newRoomId,
                'booking_id' => $booking->id,
                'booked_date' => $date->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($days)) {
            RoomBookedDate::insert($days);
        }
    }

    /**
     * Cập nhật trạng thái phòng
     */
    private function updateRoomStatuses(int $oldRoomId, int $newRoomId, Booking $booking): void
    {
        $today = now()->toDateString();
        $checkIn = $booking->check_in->toDateString();
        $checkOut = $booking->check_out->toDateString();

        // Nếu đang trong thởi gian ở
        if ($today >= $checkIn && $today < $checkOut) {
            Room::where('id', $oldRoomId)->update(['status' => 'available']);
            Room::where('id', $newRoomId)->update(['status' => 'occupied']);
        }
    }

    /**
     * Tính lại tổng tiền booking
     */
    private function recalculateBookingTotal(Booking $booking): float
    {
        $booking->refresh();
        
        $roomsTotal = $booking->bookingRooms()->sum('subtotal');
        $servicesTotal = $booking->bookingServices()->get()->sum(function ($bs) {
            return $bs->quantity * $bs->price;
        });
        $surchargesTotal = $booking->surcharges()->sum('amount');

        $newTotal = $roomsTotal + $servicesTotal + $surchargesTotal;
        
        $booking->update(['total_price' => $newTotal]);

        return $newTotal;
    }

    /**
     * Tạo lịch sử đổi phòng
     */
    private function createChangeHistory(
        int $bookingId,
        int $fromRoomId,
        int $toRoomId,
        ?string $reason,
        ?int $changedBy,
        float $oldPrice,
        float $newPrice,
        float $priceDifference
    ): RoomChangeHistory {
        return RoomChangeHistory::create([
            'booking_id' => $bookingId,
            'from_room_id' => $fromRoomId,
            'to_room_id' => $toRoomId,
            'damage_report_id' => null,
            'reason' => $reason ?? 'Khách yêu cầu đổi phòng',
            'changed_by' => $changedBy,
            'changed_at' => now(),
            'old_price_per_night' => $oldPrice,
            'new_price_per_night' => $newPrice,
            'price_difference' => $priceDifference,
        ]);
    }

    /**
     * Cập nhật payment nếu cần
     */
    private function updatePaymentIfNeeded(Booking $booking, float $newTotalPrice): bool
    {
        $payment = Payment::where('booking_id', $booking->id)
            ->orderByDesc('id')
            ->first();

        if ($payment && in_array($payment->status, ['paid', 'partial', 'pending'])) {
            $payment->update(['amount' => $newTotalPrice]);
            return true;
        }

        return false;
    }

    /**
     * Lấy lịch sử đổi phòng của một booking
     *
     * @param int $bookingId
     * @return array
     */
    public function getChangeHistory(int $bookingId): array
    {
        $histories = RoomChangeHistory::with(['fromRoom', 'toRoom', 'changedBy'])
            ->where('booking_id', $bookingId)
            ->orderByDesc('changed_at')
            ->get();

        return $histories->map(function ($history) {
            return [
                'id' => $history->id,
                'from_room' => $history->fromRoom?->name ?? 'Unknown',
                'to_room' => $history->toRoom?->name ?? 'Unknown',
                'reason' => $history->reason,
                'changed_by' => $history->changedBy?->full_name ?? 'System',
                'changed_at' => $history->changed_at->format('d/m/Y H:i'),
                'price_difference' => $history->price_difference ?? 0,
            ];
        })->toArray();
    }

    /**
     * Hoàn tác đổi phòng (chỉ dùng cho admin)
     *
     * @param int $historyId
     * @param string|null $reason
     * @param int|null $changedBy
     * @return array
     * @throws \Exception
     */
    public function revertRoomChange(int $historyId, ?string $reason = null, ?int $changedBy = null): array
    {
        $history = RoomChangeHistory::findOrFail($historyId);
        
        return $this->changeRoom(
            Booking::findOrFail($history->booking_id),
            $history->to_room_id,
            $history->from_room_id,
            'Hoàn tác: ' . ($reason ?? $history->reason),
            $changedBy
        );
    }
}
