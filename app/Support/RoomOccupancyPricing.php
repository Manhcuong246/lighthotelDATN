<?php

namespace App\Support;

use App\Models\RoomType;

/**
 * Định giá phòng theo số khách (Occupancy Surcharge).
 *
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║  Phân loại khách                                               ║
 * ║  • Người lớn (Adult)    : ≥ 12 tuổi   → tính sức chứa + phí   ║
 * ║  • Trẻ 6–11 (Older Child): 6–11 tuổi  → tính sức chứa + phí   ║
 * ║  • Trẻ 0–5 (Infant)    : 0–5 tuổi    → KHÔNG tính sức chứa, MIỄN PHÍ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  Tiêu chuẩn / tối đa sức chứa: chỉ NL + trẻ 6–11               ║
 * ║  Tối đa trẻ 0–5 mỗi phòng      = 2 (policy, không “chỗ”)      ║
 * ║  Trẻ 0–5 miễn phụ phí; vượt giới hạn 0–5 từ chối              ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  Phụ phí: chỉ khi NL + trẻ 6–11 vượt tiêu chuẩn (% giá phòng).║
 * ╚══════════════════════════════════════════════════════════════════╝
 */
final class RoomOccupancyPricing
{
    public static function standardCapacity(?RoomType $roomType = null): int
    {
        if ($roomType && !is_null($roomType->standard_capacity)) {
            return (int) $roomType->standard_capacity;
        }
        return (int) config('booking.pricing.standard_capacity', 3);
    }

    public static function maxCapacity(?RoomType $roomType = null): int
    {
        if ($roomType && !is_null($roomType->capacity)) {
            return (int) $roomType->capacity;
        }
        return (int) config('booking.pricing.max_capacity', 6);
    }

    public static function maxChildren05(): int
    {
        return (int) config('booking.pricing.max_children_05', 2);
    }

    public static function maxChildren05Free(): int
    {
        return self::maxChildren05();
    }

    public static function adultSurchargeRate(?RoomType $roomType = null): float
    {
        if ($roomType && !is_null($roomType->adult_surcharge_rate)) {
            return (float) $roomType->adult_surcharge_rate;
        }
        return (float) config('booking.pricing.default_adult_surcharge_rate', 0.25);
    }

    public static function childSurchargeRate(?RoomType $roomType = null): float
    {
        if ($roomType && !is_null($roomType->child_surcharge_rate)) {
            return (float) $roomType->child_surcharge_rate;
        }
        return (float) config('booking.pricing.default_child_surcharge_rate', 0.125);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public static function validate(int $adults, int $children611, int $children05 = 0, ?RoomType $roomType = null): void
    {
        if ($adults < 1) {
            throw new \InvalidArgumentException('Cần ít nhất 1 người lớn trong phòng.');
        }

        // Sức chứa tối đa: chỉ người lớn + trẻ 6–11 (trẻ 0–5 không tính)
        $capacityOccupancy = $adults + $children611;
        $maxCapacity = $roomType ? (int) $roomType->capacity : self::maxCapacity();

        if ($capacityOccupancy > $maxCapacity) {
            throw new \InvalidArgumentException("Số khách tính sức chứa (NL + trẻ 6–11: {$capacityOccupancy}) vượt quá sức chứa tối đa của phòng ({$maxCapacity}). Trẻ 0–5 tuổi không tính vào sức chứa.");
        }

        $maxC05 = self::maxChildren05();
        if ($children05 > $maxC05) {
            throw new \InvalidArgumentException("Trẻ em 0-5 tuổi tối đa {$maxC05} người mỗi phòng.");
        }
    }

    /**
     * Tính breakdown giá cho 1 phòng / 1 đêm.
     *
     * Trẻ 0–5 miễn phụ phí và không chiếm chỗ tiêu chuẩn / không tính sức chứa tối đa.
     * (BookingAdminController::calculateTotalPriceWithSurcharge gọi breakdown theo từng đêm.)
     */
    public static function breakdown(
        float $basePrice,
        int $adults,
        int $children611,
        int $children05 = 0,
        ?RoomType $roomType = null
    ): array {
        self::validate($adults, $children611, $children05, $roomType);

        $std = self::standardCapacity($roomType);
        $billableGuests = $adults + $children611;

        $billableSlots = max(0, $std);
        $extraAdults = max(0, $adults - $billableSlots);
        $remainingSlots = max(0, $billableSlots - $adults);
        $extraOlderChildren = max(0, $children611 - $remainingSlots);

        $aRate = self::adultSurchargeRate($roomType);
        $cRate = self::childSurchargeRate($roomType);

        $adultSurcharge = $extraAdults * $aRate * $basePrice;
        $childSurcharge = $extraOlderChildren * $cRate * $basePrice;
        $surchargePerNight = round($adultSurcharge + $childSurcharge, 2);
        $pricePerNight = round($basePrice + $surchargePerNight, 2);

        return [
            'total_occupancy' => $adults + $children611 + $children05,
            'billable_occupancy' => $billableGuests,
            'standard_capacity' => $std,
            'is_surcharge' => $extraAdults > 0 || $extraOlderChildren > 0,
            'extra_adults' => $extraAdults,
            'extra_older_children' => $extraOlderChildren,
            'adult_surcharge_rate' => $aRate,
            'child_surcharge_rate' => $cRate,
            'adult_surcharge_per_night' => round($adultSurcharge, 2),
            'child_surcharge_per_night' => round($childSurcharge, 2),
            'surcharge_per_night' => $surchargePerNight,
            'price_per_night' => $pricePerNight,
            'base_price' => $basePrice,
        ];
    }

    /**
     * Tính tổng tiền cho 1 phòng × N đêm.
     */
    public static function total(
        float $basePrice,
        int $nights,
        int $adults,
        int $children611,
        int $children05 = 0,
        ?RoomType $roomType = null
    ): array {
        $b = self::breakdown($basePrice, $adults, $children611, $children05, $roomType);

        $roomTotal = $basePrice * $nights;
        $totalSurcharge = $b['surcharge_per_night'] * $nights;
        $grandTotal = round($roomTotal + $totalSurcharge, 2);

        return array_merge($b, [
            'nights' => $nights,
            'room_total' => round($roomTotal, 2),
            'total_surcharge' => round($totalSurcharge, 2),
            'grand_total' => $grandTotal,
            'price_per_night' => $b['price_per_night'],
        ]);
    }
}
