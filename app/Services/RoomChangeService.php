<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\BookingRoom;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomBookedDate;
use App\Models\RoomChangeHistory;
use App\Support\BookingFinancialAudit;
use App\Support\InvoiceBookingSynchronizer;
use App\Support\RoomOccupancyPricing;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service xử lý chức năng đổi phòng theo nghiệp vụ khách sạn
 *
 * Luồng trực tiếp (Lễ tân/Quản lý):
 *   1. Tiếp nhận yêu cầu → 2. Kiểm tra tình trạng phòng
 *   3. Tính chênh lệch (cùng hạng $0 / nâng hạng tính theo số đêm còn lại)
 *   4. Xác nhận khách → 5. Cập nhật PMS (phòng cũ → Dirty, phòng mới → Occupied)
 *   6. Bàn giao khóa
 *
 * Luồng tự động (App/Website):
 *   1. Chọn đổi phòng → 2. Kiểm tra chính sách → 3. Chọn phòng mới
 *   4. Thanh toán chênh lệch → 5. Gửi Email/Notification
 *
 * Business Rules:
 *   - Chỉ cho đổi sang phòng status = available (Ready/Clean)
 *   - Phòng mới rẻ hơn → hoàn tiền hoặc credit
 *   - Phòng mới đắt hơn → thanh toán ngay hoặc ghi nợ Folio
 *   - Tự động push lệnh dọn phòng cho Housekeeping khi rời phòng cũ
 *   - Giới hạn giờ đổi phòng (mặc định 22:00) trừ trường hợp khẩn cấp
 *   - Phải kiểm tra sức chứa phòng mới đủ cho số lượng khách
 */
class RoomChangeService
{
    /**
     * Loại đổi phòng
     */
    public const TYPE_SAME_GRADE  = 'same_grade';   // Cùng hạng (không phụ phí)
    public const TYPE_UPGRADE     = 'upgrade';       // Nâng hạng (phụ phí)
    public const TYPE_DOWNGRADE   = 'downgrade';     // Hạ hạng (hoàn tiền)
    public const TYPE_EMERGENCY   = 'emergency';     // Khẩn cấp (hỏng thiết bị)

    /**
     * Lý do đổi phòng mặc định
     */
    public const REASONS = [
        'guest_request'    => 'Khách yêu cầu đổi phòng',
        'room_issue'       => 'Phòng bị lỗi thiết bị',
        'upgrade'          => 'Khách muốn nâng hạng',
        'noise'            => 'Tiếng ồn / không gian ồn ào',
        'view_request'     => 'Khách muốn đổi view',
        'maintenance'      => 'Bảo trì phòng',
        'emergency'        => 'Khẩn cấp kỹ thuật',
        'other'            => 'Lý do khác',
    ];

    /**
     * Kiểm tra xem có được đổi phòng vào thời điểm hiện tại không
     * Business rule: Không cho đổi sau giờ giới hạn trừ khẩn cấp
     */
    public function isWithinAllowedTime(?string $reason = null): bool
    {
        if (! config('room_changes.enforce_time_restriction', false)) {
            return true;
        }

        // Lý do khẩn cấp thì luôn cho phép
        if ($reason === self::TYPE_EMERGENCY || $reason === 'emergency') {
            return true;
        }

        $deadline = (int) config('room_changes.time_restriction_hour', 22);
        $now = Carbon::now();

        // Nếu sau giờ giới hạn và không phải khẩn cấp → chặn
        return $now->hour < $deadline;
    }

    /**
     * Tính số đêm còn lại từ hiện tại đến check-out
     * Công thức: Phí bổ sung = (Giá mới - Giá cũ) × Số đêm còn lại
     */
    public function calculateRemainingNights(Booking $booking): int
    {
        $now = Carbon::now()->startOfDay();
        $checkOut = Carbon::parse($booking->check_out)->startOfDay();

        if ($now >= $checkOut) {
            return 0; // Đã quá hạn
        }

        $checkIn = Carbon::parse($booking->check_in)->startOfDay();

        // Nếu chưa đến ngày check-in → tính toàn bộ số đêm
        if ($now < $checkIn) {
            return $checkIn->diffInDays($checkOut);
        }

        // Đang trong kỳ lưu trú → tính từ hôm nay đến check-out
        return $now->diffInDays($checkOut);
    }

    /**
     * Đêm cần phòng trống khi đổi sang phòng mới (trùng với kiểm tra lịch lúc submit).
     * Chưa vào kỳ lưu trú: từ đêm check-in đến đêm trước check-out.
     * Đã trong kỳ lưu trú: từ đêm nay đến đêm trước check-out (đêm đã qua không cần phòng mới).
     *
     * @return array{0: string, 1: string} [Y-m-d đầu, Y-m-d cuối] inclusive
     */
    private function roomChangeHoldNightRangeInclusive(Booking $booking): array
    {
        $checkIn = Carbon::parse($booking->check_in)->startOfDay();
        $checkOut = Carbon::parse($booking->check_out)->startOfDay();
        $lastNight = $checkOut->copy()->subDay();
        $today = Carbon::now()->startOfDay();

        if ($lastNight->lt($checkIn)) {
            return [$checkIn->toDateString(), $checkIn->toDateString()];
        }

        $firstNight = $checkIn;
        if ($today->gte($checkIn) && $today->lt($checkOut)) {
            $firstNight = $today;
        }

        if ($firstNight->gt($lastNight)) {
            return [$lastNight->toDateString(), $lastNight->toDateString()];
        }

        return [$firstNight->toDateString(), $lastNight->toDateString()];
    }

    /**
     * Các room_id không được chọn làm phòng đích khi đổi phòng:
     * - Mọi phòng vật lý đang gán cho đơn (tránh chọn lại đúng phòng / phòng của slot khác trên cùng đơn).
     * - Phòng có lịch đơn khác trùng các đêm cần giữ (theo roomChangeHoldNightRangeInclusive).
     * - Phòng đã gán (booking_rooms.room_id) cho đơn khác có kỳ lưu trú chồng lấn — tránh trường hợp room_booked_dates lệch/thiếu.
     */
    public function getExcludedRoomIdsForChange(Booking $booking): array
    {
        $booking->loadMissing('bookingRooms');

        $held = $booking->bookingRooms
            ->pluck('room_id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        [$rangeStart, $rangeEndNight] = $this->roomChangeHoldNightRangeInclusive($booking);

        if (Carbon::parse($rangeStart)->gt(Carbon::parse($rangeEndNight))) {
            return $held;
        }

        $blocked = RoomBookedDate::query()
            ->whereBetween('booked_date', [$rangeStart, $rangeEndNight])
            ->whereHas('booking', function ($q) use ($booking) {
                $q->where('id', '!=', $booking->id)
                    ->whereNotIn('status', ['cancelled', 'completed']);
            })
            ->distinct()
            ->pluck('room_id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        // Trùng đêm: đơn khác có check_in <= cuối đêm cần giữ và check_out > đầu đêm cần giữ (check-out là ngày rời, không tính đêm đó).
        $assignedElsewhere = BookingRoom::query()
            ->whereNotNull('room_id')
            ->where('booking_id', '!=', $booking->id)
            ->whereHas('booking', function ($q) use ($booking, $rangeStart, $rangeEndNight) {
                $q->whereNotIn('status', ['cancelled', 'completed'])
                    ->whereDate('check_in', '<=', $rangeEndNight)
                    ->whereDate('check_out', '>', $rangeStart);
            })
            ->distinct()
            ->pluck('room_id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->values()
            ->all();

        return collect($held)->merge($blocked)->merge($assignedElsewhere)->unique()->values()->all();
    }

    /**
     * Lấy danh sách phòng có thể đổi - Business rule: chỉ status = available (Ready/Clean)
     */
    public function getAvailableRoomsForChange(Booking $booking, int $currentRoomId): array
    {
        $booking->load('bookingRooms.room');
        $bookingRoom = $booking->bookingRooms->firstWhere('room_id', $currentRoomId);
        if (! $bookingRoom) {
            return [];
        }

        $currentRoom = $bookingRoom->room;
        $remainingNights = $this->calculateRemainingNights($booking);
        // Trẻ 0–5 không tính vào sức chứa khi chọn phòng mới
        $totalGuests = (int) $bookingRoom->adults + (int) $bookingRoom->children_6_11;

        $excludeIds = $this->getExcludedRoomIdsForChange($booking);

        // CHỈ lấy phòng available (Ready/Clean), không bảo trì — Business rule; không trùng phòng đang giữ / đã bị book
        $query = Room::with('roomType')
            ->where('status', 'available')
            ->excludeMaintenance();
        if ($excludeIds !== []) {
            $query->whereNotIn('id', $excludeIds);
        }

        // Sắp xếp: cùng hạng → nâng hạng → hạ hạng
        if ($currentRoom && $currentRoom->room_type_id) {
            $currentPrice = $currentRoom->catalogueBasePrice();
            $query->orderByRaw("
                CASE 
                    WHEN room_type_id = ? THEN 0
                    WHEN (SELECT price FROM room_types WHERE room_types.id = rooms.room_type_id AND room_types.deleted_at IS NULL) > ? THEN 1
                    ELSE 2
                END ASC
            ", [$currentRoom->room_type_id, $currentPrice]);
        }

        return $query->get()->map(function ($room) use ($currentRoom, $remainingNights, $totalGuests) {
            $isSameType = $currentRoom && $room->room_type_id === $currentRoom->room_type_id;
            $currentPrice = $currentRoom ? $currentRoom->catalogueBasePrice() : 0;
            $newPrice = $room->catalogueBasePrice();
            $priceDiffPerNight = $newPrice - $currentPrice;

            $changeType = self::TYPE_SAME_GRADE;
            if ($priceDiffPerNight > 0) {
                $changeType = self::TYPE_UPGRADE;
            } elseif ($priceDiffPerNight < 0) {
                $changeType = self::TYPE_DOWNGRADE;
            }

            $totalDiff = $priceDiffPerNight * $remainingNights;
            $maxGuests = $room->catalogueMaxGuests();

            return [
                'id'                     => $room->id,
                'name'                   => $room->name,
                'room_number'            => $room->room_number ?? $room->name,
                'room_type'              => $room->roomType ? ['id' => $room->roomType->id, 'name' => $room->roomType->name, 'price' => $room->roomType->price ?? 0] : null,
                'base_price'             => $newPrice,
                'max_guests'             => $maxGuests,
                'is_same_type'           => $isSameType,
                'change_type'            => $changeType,
                'price_diff_per_night'   => $priceDiffPerNight,
                'remaining_nights'       => $remainingNights,
                'total_price_difference' => $totalDiff,
                'has_capacity'           => $maxGuests >= $totalGuests,
                'current_guests'         => $totalGuests,
                'status'                 => $room->status,
                'is_available_now'       => $room->status === 'available',
            ];
        })->filter(static fn (array $row): bool => $row['has_capacity'] === true)->values()->toArray();
    }

    /**
     * Trùng lịch room_booked_dates từ đơn khác (đơn hiện tại đã loại trừ).
     *
     * @param  string  $firstNightYmd  Đêm đầu (inclusive, Y-m-d)
     * @param  string  $lastNightYmd  Đêm cuối (inclusive, Y-m-d)
     */
    private function roomHasCalendarConflictFromOtherBookings(
        int $roomId,
        string $firstNightYmd,
        string $lastNightYmd,
        ?int $excludeBookingId = null
    ): bool {
        return RoomBookedDate::where('room_id', $roomId)
            ->whereBetween('booked_date', [$firstNightYmd, $lastNightYmd])
            ->whereHas('booking', function ($q) use ($excludeBookingId) {
                $q->whereNotIn('status', ['cancelled', 'completed']);
                if ($excludeBookingId) {
                    $q->where('id', '!=', $excludeBookingId);
                }
            })
            ->exists();
    }

    /**
     * Phòng đã gán vật lý (booking_rooms.room_id) cho đơn khác có kỳ chồng khoảng đêm — bắt lệch khi room_booked_dates chưa đồng bộ.
     */
    private function roomHasAssignmentConflictFromOtherBookings(
        int $roomId,
        string $firstNightYmd,
        string $lastNightYmd,
        ?int $excludeBookingId = null
    ): bool {
        return BookingRoom::query()
            ->where('room_id', $roomId)
            ->when($excludeBookingId !== null && $excludeBookingId > 0, function ($q) use ($excludeBookingId) {
                $q->where('booking_id', '!=', $excludeBookingId);
            })
            ->whereHas('booking', function ($q) use ($firstNightYmd, $lastNightYmd) {
                $q->whereNotIn('status', ['cancelled', 'completed'])
                    ->whereDate('check_in', '<=', $lastNightYmd)
                    ->whereDate('check_out', '>', $firstNightYmd);
            })
            ->exists();
    }

    /**
     * Xung đột đầy đủ: lịch room_booked_dates hoặc gán booking_rooms (đơn khác, kỳ chồng).
     */
    private function roomHasInventoryConflictForHoldRange(
        int $roomId,
        string $firstNightYmd,
        string $lastNightYmd,
        ?int $excludeBookingId = null
    ): bool {
        return $this->roomHasCalendarConflictFromOtherBookings($roomId, $firstNightYmd, $lastNightYmd, $excludeBookingId)
            || $this->roomHasAssignmentConflictFromOtherBookings($roomId, $firstNightYmd, $lastNightYmd, $excludeBookingId);
    }

    /**
     * Phòng trống trên lịch + trạng thái «available» và không bảo trì.
     *
     * @param int $excludeBookingId Loại trừ đơn đang đổi (không tính chỗ giữ của chính đơn đó).
     */
    public function isRoomAvailable(int $roomId, string $checkIn, string $checkOut, ?int $excludeBookingId = null): bool
    {
        $room = Room::query()->find($roomId);
        if (! $room || $room->isInMaintenance() || $room->status !== 'available') {
            return false;
        }

        $first = Carbon::parse($checkIn)->toDateString();
        $last = Carbon::parse($checkOut)->copy()->subDay()->toDateString();

        return ! $this->roomHasInventoryConflictForHoldRange($roomId, $first, $last, $excludeBookingId);
    }

    /**
     * Danh sách phòng cho màn đổi phòng (admin/staff) — cùng logic loại trừ với {@see changeRoom()}.
     *
     * @return list<array<string, mixed>>
     */
    public function getCandidateRoomsPayloadForChangeScreen(Booking $booking, ?int $oldRoomId = null): array
    {
        $booking->loadMissing('bookingRooms.room.roomType');

        $bookingRoom = $oldRoomId
            ? $booking->bookingRooms->firstWhere('room_id', $oldRoomId)
            : null;
        if (! $bookingRoom) {
            $bookingRoom = $booking->bookingRooms->first();
        }
        if (! $bookingRoom) {
            return [];
        }

        $excludeIds = $this->getExcludedRoomIdsForChange($booking);

        $query = Room::with('roomType')
            ->where('status', 'available')
            ->excludeMaintenance();
        if ($excludeIds !== []) {
            $query->whereNotIn('id', $excludeIds);
        }

        $totalGuests = (int) $bookingRoom->adults + (int) $bookingRoom->children_6_11;

        return $query->get()
            ->filter(static fn (Room $room) => $room->catalogueMaxGuests() >= $totalGuests)
            ->map(function (Room $room) use ($bookingRoom) {
                $rt = $room->roomType;
                $base = (float) $room->catalogueBasePrice();

                $breakdown = RoomOccupancyPricing::breakdown(
                    $base,
                    (int) $bookingRoom->adults,
                    (int) $bookingRoom->children_6_11,
                    (int) $bookingRoom->children_0_5,
                    $rt
                );

                return [
                    'id' => $room->id,
                    'room_number' => $room->room_number ?? $room->name ?? 'N/A',
                    'room_type' => $rt->name ?? 'N/A',
                    'price' => $breakdown['base_price'],
                    'price_per_night_full' => $breakdown['price_per_night'],
                    'surcharge_per_night' => $breakdown['surcharge_per_night'],
                    'capacity' => $rt->capacity ?? 0,
                    'standard_capacity' => $rt->standard_capacity ?? $rt->capacity ?? 0,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Thực hiện đổi phòng theo nghiệp vụ khách sạn
     *
     * Luồng: Validation → Execution → Housekeeping → Payment → Logging
     *
     * @param Booking $booking
     * @param int $oldRoomId
     * @param int $newRoomId
     * @param string|null $reason
     * @param int|null $changedBy
     * @param int|null $damageReportId Gắn lịch sử đổi phòng với báo hư hỏng (nếu có)
     * @param bool $isEmergency Bỏ qua giới hạn giờ
     * @param bool $keepLegacyPrice Giữ nguyên đơn giá trên dòng booking_room (không tính lại theo catalogue phòng mới)
     * @return array
     * @throws \Exception
     */
    public function changeRoom(
        Booking $booking,
        int $oldRoomId,
        int $newRoomId,
        ?string $reason = null,
        ?int $changedBy = null,
        ?int $damageReportId = null,
        bool $isEmergency = false,
        bool $keepLegacyPrice = false
    ): array {
        if ($oldRoomId === $newRoomId) {
            throw new \Exception('Phòng mới trùng phòng cũ.');
        }

        // === VALIDATION (Bước 2: Kiểm tra tình trạng phòng) ===

        // Kiểm tra giới hạn giờ (trừ khẩn cấp)
        if (! $isEmergency && ! $this->isWithinAllowedTime($reason)) {
            $deadline = config('room_changes.time_restriction_hour', 22);
            throw new \Exception("Không thể đổi phòng sau {$deadline}:00. Chỉ cho phép trong trường hợp khẩn cấp.");
        }

        [$holdFirstNight, $holdLastNight] = $this->roomChangeHoldNightRangeInclusive($booking);
        if ($this->roomHasInventoryConflictForHoldRange($newRoomId, $holdFirstNight, $holdLastNight, $booking->id)) {
            throw new \Exception(
                'Phòng mới không khả dụng trong khoảng ngày cần thiết (đơn khác đã giữ lịch hoặc đã được gán phòng vật lý).'
            );
        }

        $bookingRoomPre = BookingRoom::where('booking_id', $booking->id)
            ->where('room_id', $oldRoomId)
            ->first();
        if (! $bookingRoomPre) {
            throw new \Exception('Phòng cũ không thuộc đơn này hoặc đã được cập nhật — tải lại trang và thử lại.');
        }

        // === EXECUTION (khóa đơn + dòng phòng + phòng đích để tránh race) ===
        return DB::transaction(function () use (
            $booking,
            $oldRoomId,
            $newRoomId,
            $reason,
            $changedBy,
            $damageReportId,
            $isEmergency,
            $keepLegacyPrice,
            $holdFirstNight,
            $holdLastNight
        ) {
            $bookingLocked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $bookingRoom = BookingRoom::query()
                ->where('booking_id', $bookingLocked->id)
                ->where('room_id', $oldRoomId)
                ->lockForUpdate()
                ->first();
            if (! $bookingRoom) {
                throw new \RuntimeException('Phòng cũ không còn gán cho đơn (có thể đơn vừa đổi phòng ở tab khác).');
            }

            $newRoom = Room::with('roomType')->whereKey($newRoomId)->lockForUpdate()->firstOrFail();
            $oldRoom = Room::whereKey($oldRoomId)->first();

            if ($this->roomHasInventoryConflictForHoldRange($newRoomId, $holdFirstNight, $holdLastNight, $bookingLocked->id)) {
                throw new \Exception(
                    'Phòng mới vừa không còn trống (đơn khác đã giữ lịch hoặc gán phòng) — chọn phòng khác.'
                );
            }

            if ($newRoom->isInMaintenance()) {
                throw new \Exception(
                    'Phòng mới đang bảo trì / chờ dọn (không đổi sang được). Kiểm tra ghi chú bảo trì hoặc đặt phòng về Trống sau khi dọn xong.'
                );
            }

            if ($newRoom->status !== 'available') {
                throw new \Exception(
                    'Phòng mới đang ở trạng thái «'.$newRoom->status.'», không phải Trống — hệ thống chỉ cho đổi sang phòng available. '.
                    '(Nếu lịch đã rảnh nhưng trạng thái chưa cập nhật, vào danh mục phòng và đặt Trống.)'
                );
            }

            $totalGuests = (int) $bookingRoom->adults + (int) $bookingRoom->children_6_11;
            $maxGuests = $newRoom->catalogueMaxGuests();
            if ($totalGuests > $maxGuests) {
                throw new \Exception("Phòng mới chỉ chứa tối đa {$maxGuests} khách (NL + trẻ 6–11; trẻ 0–5 không tính), hiện có {$totalGuests} khách tính sức chứa.");
            }

            $nights = max(1, (int) $bookingRoom->nights);
            $remainingNights = $this->calculateRemainingNights($bookingLocked);
            $oldPricePerNight = (float) $bookingRoom->price_per_night;

            // === Bước 3: Tính chênh lệch ===
            $newPricePerNight = $keepLegacyPrice
                ? $oldPricePerNight
                : $this->calculateNewPrice($bookingRoom, $oldRoom, $newRoom, $oldPricePerNight);

            // Xác định loại đổi phòng
            $changeType = self::TYPE_SAME_GRADE;
            if ($newPricePerNight > $oldPricePerNight) {
                $changeType = self::TYPE_UPGRADE;
            } elseif ($newPricePerNight < $oldPricePerNight) {
                $changeType = self::TYPE_DOWNGRADE;
            }
            if ($isEmergency) {
                $changeType = self::TYPE_EMERGENCY;
            }

            // Đồng bộ với cập nhật lịch: chỉ khi đơn đã check-in mới tách «đêm đã ở» vs «đêm còn lại»
            $isCheckedIn = $bookingLocked->status === 'checked_in';
            $remainingForPricing = min(max(0, $remainingNights), $nights);
            if ($isCheckedIn && $remainingForPricing > 0) {
                // Đã check-in: chỉ tính chênh lệch cho số đêm còn lại
                $nightsAlreadyUsed = max(0, $nights - $remainingForPricing);
                $oldSubtotal = $oldPricePerNight * $nights;
                $newSubtotal = ($oldPricePerNight * $nightsAlreadyUsed) + ($newPricePerNight * $remainingForPricing);
                $priceDifference = $newSubtotal - $oldSubtotal;
            } else {
                // Chưa check-in: tính lại toàn bộ
                $newSubtotal = $newPricePerNight * $nights;
                $oldSubtotal = (float) $bookingRoom->subtotal;
                $priceDifference = $newSubtotal - $oldSubtotal;
            }

            // === Bước 5: Cập nhật PMS ===
            // 4. Cập nhật booking_rooms
            $bookingRoom->update([
                'room_id' => $newRoomId,
                'price_per_night' => $newPricePerNight,
                'subtotal' => $newSubtotal,
            ]);

            // Đồng bộ bookings.room_id (legacy / màn hình cũ) khi trùng phòng đổi hoặc đơn chỉ một dòng phòng
            if ((int) ($bookingLocked->room_id ?? 0) === $oldRoomId
                || BookingRoom::where('booking_id', $bookingLocked->id)->count() === 1) {
                Booking::where('id', $bookingLocked->id)->update(['room_id' => $newRoomId]);
            }

            // 5. Cập nhật room_booked_dates
            $this->updateRoomBookedDates($bookingLocked, $oldRoomId, $newRoomId);

            // 6. Cập nhật trạng thái phòng (ENUM DB: available | booked | maintenance)
            $oldRoomStatus = $this->updateRoomStatuses($oldRoomId, $newRoomId, $bookingLocked);

            // 7. Tính lại tổng tiền booking
            $newTotalPrice = $this->recalculateBookingTotal($bookingLocked);

            // 8. Ghi lịch sử đổi phòng
            $history = $this->createChangeHistory(
                $bookingLocked->id, $oldRoomId, $newRoomId, $reason, $changedBy,
                $oldPricePerNight, $newPricePerNight, $priceDifference,
                $changeType, $remainingForPricing, $oldRoomStatus,
                $damageReportId
            );

            // 9. Xử lý tài chính (Bước 3 tiếp)
            $paymentUpdate = $this->handleFinancialAdjustment($bookingLocked, $newTotalPrice, $priceDifference);

            // 10. Ghi BookingLog
            BookingLog::create([
                'booking_id'  => $bookingLocked->id,
                'user_id'     => $changedBy,
                'old_status'  => $bookingLocked->status,
                'new_status'  => $bookingLocked->status,
                'notes'       => "Đổi phòng: " . ($oldRoom?->name ?? '#'.$oldRoomId) . " → " . $newRoom->name . ($reason ? " ({$reason})" : ''),
                'changed_at'  => now(),
            ]);

            // 11. Ghi log hệ thống
            Log::info('Room change completed', [
                'booking_id'      => $bookingLocked->id,
                'from_room_id'    => $oldRoomId,
                'to_room_id'      => $newRoomId,
                'change_type'     => $changeType,
                'price_difference'=> $priceDifference,
                'remaining_nights'=> $remainingForPricing,
                'changed_by'      => $changedBy,
            ]);

            $bookingLocked->refresh();
            BookingFinancialAudit::record($bookingLocked, 'room_change', [
                'room_change_history_id' => $history->id,
                'from_room_id' => $oldRoomId,
                'to_room_id' => $newRoomId,
                'reason' => $reason,
                'price_difference' => $priceDifference,
                'change_type' => $changeType,
                'remaining_nights' => $remainingForPricing,
                'old_room_subtotal' => $oldSubtotal,
                'new_room_subtotal' => $newSubtotal,
                'payment_adjustment_attempted' => $paymentUpdate,
            ], $changedBy);

            try {
                InvoiceBookingSynchronizer::syncFullFromBooking($bookingLocked->fresh());
            } catch (\Throwable $e) {
                Log::warning('invoice_sync_after_room_change_failed', [
                    'booking_id' => $bookingLocked->id,
                    'message' => $e->getMessage(),
                ]);
            }

            return [
                'success'          => true,
                'history_id'       => $history->id,
                'old_room'         => $oldRoom?->name ?? 'Unknown',
                'new_room'         => $newRoom->name,
                'old_price'        => $oldSubtotal,
                'new_price'        => $newSubtotal,
                'price_difference' => $priceDifference,
                'change_type'      => $changeType,
                'remaining_nights' => $remainingForPricing,
                'payment_updated'  => $paymentUpdate,
            ];
        });
    }

    /**
     * Tính giá / đêm mới (giá gốc + phụ phí khách theo % loại phòng).
     */
    private function calculateNewPrice(BookingRoom $bookingRoom, ?Room $oldRoom, Room $newRoom, float $oldPricePerNight): float
    {
        if ($oldRoom && $oldRoom->room_type_id === $newRoom->room_type_id) {
            return $oldPricePerNight;
        }

        $newRoom->loadMissing('roomType');
        $base = (float) $newRoom->catalogueBasePrice();
        $rt = $newRoom->roomType;

        $b = RoomOccupancyPricing::breakdown(
            $base,
            (int) $bookingRoom->adults,
            (int) $bookingRoom->children_6_11,
            (int) $bookingRoom->children_0_5,
            $rt
        );

        return (float) $b['price_per_night'];
    }

    /**
     * Cập nhật room_booked_dates
     */
    private function updateRoomBookedDates(Booking $booking, int $oldRoomId, int $newRoomId): void
    {
        $checkIn = Carbon::parse($booking->check_in)->startOfDay();
        $checkOut = Carbon::parse($booking->check_out)->startOfDay();
        $lastNight = $checkOut->copy()->subDay();

        if ($checkIn->gt($lastNight)) {
            return;
        }

        $today = Carbon::now()->startOfDay();

        if ($booking->status === 'checked_in' && $today->lt($checkOut)) {
            $periodStart = $today->gt($checkIn) ? $today : $checkIn;
            if ($periodStart->gt($lastNight)) {
                return;
            }
            $period = CarbonPeriod::create($periodStart, $lastNight);
            RoomBookedDate::replaceBookingRoomNights($booking->id, $oldRoomId, $newRoomId, $period);

            return;
        }

        $period = CarbonPeriod::create($checkIn, $lastNight);
        RoomBookedDate::replaceBookingRoomNights($booking->id, $oldRoomId, $newRoomId, $period);
    }

    /**
     * Phòng cũ đang lưu trú: đánh dấu maintenance + ghi chú dọn (không dùng ENUM cleaning — DB thường chỉ có available/booked/maintenance).
     * Phòng mới đang lưu trú: booked (đồng nghĩa đang có khách / đã giữ chỗ).
     */
    private function updateRoomStatuses(int $oldRoomId, int $newRoomId, Booking $booking): string
    {
        $today = now()->toDateString();
        $checkIn = $booking->check_in instanceof \Carbon\CarbonInterface
            ? $booking->check_in->toDateString()
            : Carbon::parse($booking->check_in)->toDateString();
        $checkOut = $booking->check_out instanceof \Carbon\CarbonInterface
            ? $booking->check_out->toDateString()
            : Carbon::parse($booking->check_out)->toDateString();
        $oldRoomStatus = Room::find($oldRoomId)?->status ?? 'available';

        // Chỉ đánh dấu phòng cũ cần dọn khi khách thực sự đang ở (đã check-in), tránh maintenance oan khi đơn confirmed chưa vào phòng.
        $guestInHouse = $booking->status === 'checked_in'
            && $today >= $checkIn
            && $today < $checkOut;

        if ($guestInHouse) {
            Room::where('id', $oldRoomId)->update([
                'status'           => 'maintenance',
                'maintenance_note' => 'Cần dọn dẹp sau khi khách đổi phòng từ đơn #' . $booking->id,
            ]);
            Room::where('id', $newRoomId)->update(['status' => 'booked']);

            Log::info('Housekeeping notification: room needs cleaning', [
                'room_id'    => $oldRoomId,
                'booking_id' => $booking->id,
                'action'     => 'auto_cleaning_after_room_change',
            ]);
        } else {
            // Đơn chưa check-in (hoặc ngoài kỳ ở): phóng phòng cũ về trống, giữ chỗ phòng mới trên lịch
            Room::where('id', $oldRoomId)->update(['status' => 'available']);
            if ($today < $checkOut) {
                Room::where('id', $newRoomId)->update(['status' => 'booked']);
            }
        }

        return $oldRoomStatus;
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
        $discount = (float) ($booking->discount_amount ?? 0);

        $newTotal = max(0.0, $roomsTotal + $servicesTotal + $surchargesTotal - $discount);

        $booking->update(['total_price' => $newTotal]);

        return $newTotal;
    }

    /**
     * Tạo lịch sử đổi phòng với đầy đủ thông tin nghiệp vụ
     */
    private function createChangeHistory(
        int $bookingId, int $fromRoomId, int $toRoomId,
        ?string $reason, ?int $changedBy,
        float $oldPrice, float $newPrice, float $priceDifference,
        string $changeType = self::TYPE_SAME_GRADE,
        int $remainingNights = 0,
        string $oldRoomStatus = 'available',
        ?int $damageReportId = null
    ): RoomChangeHistory {
        return RoomChangeHistory::create([
            'booking_id'         => $bookingId,
            'from_room_id'       => $fromRoomId,
            'to_room_id'         => $toRoomId,
            'damage_report_id'   => $damageReportId,
            'reason'             => $reason ?? 'Khách yêu cầu đổi phòng',
            'changed_by'         => $changedBy,
            'changed_at'         => now(),
            'old_price_per_night'=> $oldPrice,
            'new_price_per_night'=> $newPrice,
            'price_difference'   => $priceDifference,
            'change_type'        => $changeType,
            'remaining_nights'   => $remainingNights,
            'old_room_status'    => $oldRoomStatus,
        ]);
    }

    /**
     * Xử lý tài chính theo nghiệp vụ KS
     * - Phòng mới rẻ hơn → hoàn tiền hoặc credit
     * - Phòng mới đắt hơn → thanh toán ngay hoặc ghi nợ Folio
     */
    private function handleFinancialAdjustment(Booking $booking, float $newTotalPrice, float $priceDifference): bool
    {
        $payment = Payment::where('booking_id', $booking->id)
            ->orderByDesc('id')
            ->first();

        if (! $payment) {
            $booking->reconcilePaymentStatusWithPayments();

            return false;
        }

        // Chỉ chỉnh amount khi giao dịch còn pending — paid là tiền đã vào quỹ, không được nhân với tổng đơn mới.
        if ($payment->status === 'pending') {
            $payment->update(['amount' => $newTotalPrice]);
        }

        $booking->reconcilePaymentStatusWithPayments();

        if ($priceDifference < 0) {
            // Hạ hạng: phát sinh hoàn tiền hoặc credit
            Log::info('Room change: downgrade refund/credit', [
                'booking_id'       => $booking->id,
                'refund_amount'    => abs($priceDifference),
                'action'           => config('room_changes.downgrade_policy', 'credit'),
            ]);
        } elseif ($priceDifference > 0) {
            // Nâng hạng: cần thanh toán bổ sung hoặc ghi nợ Folio
            Log::info('Room change: upgrade surcharge', [
                'booking_id'       => $booking->id,
                'surcharge_amount' => $priceDifference,
                'action'           => config('room_changes.upgrade_policy', 'add_to_folio'),
            ]);
        }

        return true;
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
            $changedBy,
            null,
            true,
            false
        );
    }
}
