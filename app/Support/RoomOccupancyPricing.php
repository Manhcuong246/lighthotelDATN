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
 * ║  • Trẻ 0–5 (Infant)    : 0–5 tuổi    → tính sức chứa, MIỄN PHÍ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  Tiêu chuẩn (Standard Capacity) = 3 (TẤT CẢ khách)            ║
 * ║  Tối đa     (Max Capacity)      = 6 (TẤT CẢ khách)            ║
 * ║  Tối đa trẻ 0–5 mỗi phòng      = 3                            ║
 * ║  ≤ 3: không phụ phí  │  > 3: phụ phí  │  > 6: từ chối          ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  Trẻ 0–5 chiếm chỗ tiêu chuẩn trước (free), chỗ còn lại cho  ║
 * ║  NL + trẻ 6–11. Ai vượt chỗ thì tính phụ phí (% giá phòng).  ║
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

    public static function maxCapacity(): int
    {
        return (int) config('booking.pricing.max_capacity', 6);
    }

    public static function maxChildren05(): int
    {
        return 2; // Chỉ 2 trẻ 0-5 được miễn phí
    }

    public static function maxChildren05Free(): int
    {
        return 2; // Số trẻ 0-5 miễn phí
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

        // Không giới hạn cứng số trẻ 0-5, nhưng trẻ thứ 3+ sẽ tính phụ phí

        $total = $adults + $children611 + $children05;
        $max = ($roomType && !is_null($roomType->capacity))
            ? (int) $roomType->capacity
            : self::maxCapacity();

        if ($total > $max) {
            throw new \InvalidArgumentException(
                "Phòng tối đa {$max} người (bao gồm trẻ em). Hiện tại: {$total} người."
            );
        }
    }

    /**
     * Tính breakdown giá cho 1 phòng / 1 đêm.
     *
     * Trẻ 0–5 chiếm slot tiêu chuẩn trước (miễn phí). Slot còn lại dành
     * cho NL + trẻ 6–11; ai vượt slot thì chịu phụ phí.
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
        $max = self::maxCapacity();
        $total = $adults + $children611 + $children05;

        // 2 trẻ 0-5 đầu miễn phí, từ trẻ thứ 3 tính phụ phí
        $c05Free = min($children05, 2);
        $c05Pay = max(0, $children05 - 2);

        // Không trừ trẻ 0-5 miễn phí khỏi slot
        $billableSlots = $std;
        $extraAdults = max(0, $adults - $billableSlots);
        $remainingSlots = max(0, $billableSlots - $adults);
        // Trẻ phải trả phí = trẻ 6-11 + trẻ 0-5 thứ 3+
        $totalPayChildren = $children611 + $c05Pay;
        $extraOlderChildren = max(0, $totalPayChildren - $remainingSlots);

        $aRate = self::adultSurchargeRate($roomType);
        $cRate = self::childSurchargeRate($roomType);

        $adultSurcharge = $extraAdults * $aRate * $basePrice;
        $childSurcharge = $extraOlderChildren * $cRate * $basePrice;
        $surchargePerNight = round($adultSurcharge + $childSurcharge, 2);
        $pricePerNight = round($basePrice + $surchargePerNight, 2);

        return [
            'total_occupancy' => $total,
            'effective_occupancy' => $total,
            'standard_capacity' => $std,
            'is_surcharge' => $total > $std,
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
