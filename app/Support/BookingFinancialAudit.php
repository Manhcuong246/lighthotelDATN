<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\BookingFinancialAuditLog;
use App\Models\BookingRoom;
use App\Models\BookingService;
use App\Models\BookingSurcharge;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

/**
 * Ghi nhận snapshot tài chính (đơn + payments + phòng/phụ phí) để đối soát theo thời gian.
 */
final class BookingFinancialAudit
{
    /**
     * @return array<string, mixed>
     */
    public static function financialSnapshot(Booking $booking): array
    {
        $bid = (int) $booking->getKey();
        if ($bid <= 0) {
            return [];
        }

        $servicesSum = (float) BookingService::query()
            ->where('booking_id', $bid)
            ->get(['quantity', 'price'])
            ->sum(static fn ($row) => (float) $row->price * (int) $row->quantity);

        return [
            'total_price' => (float) ($booking->total_price ?? 0),
            'payment_status' => (string) ($booking->payment_status ?? ''),
            'booking_status' => (string) ($booking->status ?? ''),
            'discount_amount' => (float) ($booking->discount_amount ?? 0),
            'room_subtotal_sum' => (float) BookingRoom::where('booking_id', $bid)->sum('subtotal'),
            'services_sum' => $servicesSum,
            'surcharges_sum' => (float) BookingSurcharge::where('booking_id', $bid)->sum('amount'),
            'paid_sum' => (float) Payment::query()->where('booking_id', $bid)->where('status', 'paid')->sum('amount'),
            'pending_sum' => (float) Payment::query()->where('booking_id', $bid)->where('status', 'pending')->sum('amount'),
            'payments' => Payment::query()
                ->where('booking_id', $bid)
                ->orderBy('id')
                ->get(['id', 'amount', 'status', 'method', 'transaction_id', 'paid_at'])
                ->map(static fn (Payment $p) => [
                    'id' => $p->id,
                    'amount' => (float) $p->amount,
                    'status' => (string) $p->status,
                    'method' => (string) ($p->method ?? ''),
                    'transaction_id' => (string) ($p->transaction_id ?? ''),
                    'paid_at' => $p->paid_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function record(Booking $booking, string $action, array $context = [], ?int $userId = null): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('booking_financial_audit_logs')) {
            return;
        }

        BookingFinancialAuditLog::create([
            'booking_id' => $booking->id,
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'context' => array_merge($context, [
                'snapshot_after' => self::financialSnapshot($booking),
            ]),
        ]);
    }
}
