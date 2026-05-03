<?php

namespace App\Support;

use App\Models\Booking;
use Carbon\Carbon;

/**
 * Chính sách hủy / hoàn tiền thống nhất (mốc nhận phòng 14:00, theo booking.check_in_date hoặc check_in).
 * Dùng chênh lệch giây thay vì diffInHours để tránh làm tròn sai ngưỡng 24h.
 */
final class CancellationRefundPolicy
{
    public const FULL_REFUND_IF_SECONDS_BEFORE_CHECK_IN_GT = 86400;

    public static function resolvePolicyCheckIn(Booking $booking): Carbon
    {
        $base = $booking->check_in_date ?? $booking->check_in;
        if ($base === null || $base === '') {
            return Carbon::now();
        }

        return Carbon::parse($base)->setTime(14, 0, 0);
    }

    /**
     * Giây đến mốc nhận phòng theo chính sách (âm = đã qua mốc).
     */
    public static function secondsUntilPolicyCheckIn(Booking $booking): int
    {
        return self::resolvePolicyCheckIn($booking)->getTimestamp() - Carbon::now()->getTimestamp();
    }

    /**
     * @return array{refund_amount: float, refund_type: 'full'|'partial'|'none', refund_percentage: int, seconds_until_check_in: int}
     */
    public static function refundBreakdown(Booking $booking, bool $isPaymentRecordedPaid): array
    {
        $seconds = self::secondsUntilPolicyCheckIn($booking);
        if (! $isPaymentRecordedPaid) {
            return [
                'refund_amount' => 0.0,
                'refund_type' => 'none',
                'refund_percentage' => 0,
                'seconds_until_check_in' => $seconds,
            ];
        }

        $total = (float) ($booking->total_price ?? 0);

        if ($seconds > self::FULL_REFUND_IF_SECONDS_BEFORE_CHECK_IN_GT) {
            return [
                'refund_amount' => $total,
                'refund_type' => 'full',
                'refund_percentage' => 100,
                'seconds_until_check_in' => $seconds,
            ];
        }

        if ($seconds > 0) {
            return [
                'refund_amount' => $total * 0.5,
                'refund_type' => 'partial',
                'refund_percentage' => 50,
                'seconds_until_check_in' => $seconds,
            ];
        }

        return [
            'refund_amount' => 0.0,
            'refund_type' => 'none',
            'refund_percentage' => 0,
            'seconds_until_check_in' => $seconds,
        ];
    }

    /**
     * Khách (web): được phép dùng luồng hủy tự động chỉ khi chưa tới mốc nhận phòng (đơn đã thanh toán).
     * Đơn chưa thanh toán vẫn được hủy bất kỳ lúc nào (không phát sinh hoàn tiền).
     */
    public static function customerWebCancelAllowed(Booking $booking, bool $isPaymentRecordedPaid): bool
    {
        if (! $isPaymentRecordedPaid) {
            return true;
        }

        return self::secondsUntilPolicyCheckIn($booking) > 0;
    }
}
