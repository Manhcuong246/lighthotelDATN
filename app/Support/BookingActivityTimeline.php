<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\BookingService;
use App\Models\BookingSurcharge;
use App\Models\Payment;
use App\Models\RefundLog;
use App\Models\RoomChangeHistory;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Gộp nhiều nguồn (booking_logs, thanh toán, dịch vụ, phụ phí, đổi phòng, hoàn tiền)
 * thành một timeline duy nhất cho màn chi tiết đặt phòng admin.
 */
final class BookingActivityTimeline
{
    private const SORT_LOG = 100;

    private const SORT_PAYMENT = 92;

    private const SORT_REFUND = 93;

    private const SORT_BOOKING_CREATED = 90;

    private const SORT_ROOM_CHANGE = 88;

    private const SORT_SERVICE = 82;

    private const SORT_SURCHARGE = 81;

    public static function forBooking(Booking $booking): Collection
    {
        $booking->loadMissing([
            'logs.user',
            'payments',
            'bookingServices.service',
            'surcharges.service',
            'refundLogs.processor',
            'roomChangeHistories.fromRoom',
            'roomChangeHistories.toRoom',
            'roomChangeHistories.changedBy',
            'invoice',
        ]);

        $events = collect();

        foreach ($booking->logs as $log) {
            $events->push(self::fromBookingLog($log));
        }

        if (! self::bookingHasCreationLog($booking)) {
            self::maybePushBookingCreated($booking, $events);
        }

        foreach ($booking->payments as $payment) {
            $row = self::fromPayment($payment);
            if ($row !== null) {
                $events->push($row);
            }
        }

        foreach ($booking->bookingServices as $bookingService) {
            $events->push(self::fromBookingService($bookingService));
        }

        foreach ($booking->surcharges as $sur) {
            $events->push(self::fromSurcharge($sur));
        }

        foreach ($booking->roomChangeHistories as $rch) {
            $events->push(self::fromRoomChange($rch));
        }

        foreach ($booking->refundLogs as $refund) {
            $events->push(self::fromRefund($refund));
        }

        if ($booking->invoice) {
            self::maybePushInvoice($booking->invoice->issued_at ?? $booking->invoice->paid_at ?? $booking->invoice->updated_at ?? $booking->invoice->created_at, $booking->invoice->invoice_number, $events);
        }

        return $events
            ->filter(fn ($e) => isset($e['at']) && $e['at'] instanceof CarbonInterface)
            ->sort(fn (array $a, array $b) => self::sorter($a, $b))
            ->values();
    }

    private static function bookingHasCreationLog(Booking $booking): bool
    {
        return $booking->logs->contains(fn ($l) => ($l instanceof BookingLog) && $l->old_status === 'new');
    }

    private static function maybePushBookingCreated(Booking $booking, Collection $events): void
    {
        $at = $booking->created_at;
        if ($at instanceof CarbonInterface) {
            $events->push([
                'kind' => 'booking_created',
                'badge' => 'Đơn',
                'badge_class' => 'bg-secondary-subtle text-secondary-emphasis border',
                'title' => 'Đơn được tạo',
                'detail' => self::placedViaLabel($booking->placed_via),
                'at' => $at->copy()->timezone(config('app.timezone')),
                'sort' => self::SORT_BOOKING_CREATED,
                'actor' => null,
                'money' => null,
            ]);
        }
    }

    private static function fromBookingLog(BookingLog $log): array
    {
        $detailParts = [];

        $oldLbl = self::statusLabel((string) $log->old_status);
        $newLbl = self::statusLabel((string) $log->new_status);

        $title = 'Trạng thái: '.$oldLbl.' → '.$newLbl;

        $notes = trim((string) ($log->notes ?? ''));
        if ($notes !== '') {
            $detailParts[] = $notes;
        }
        return [
            'kind' => 'status',
            'badge' => 'Trạng thái',
            // Phải có màu chữ + nền: nhiều layout đặt .badge { color: #fff } — chỉ class "border"
            // khiến chữ trắng trên nền sáng (nhìn như nhãn trống).
            'badge_class' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
            'title' => $title,
            'detail' => $detailParts === [] ? null : implode(' — ', $detailParts),
            'at' => self::normalizeCarbon($log->changed_at ?? now()),
            'sort' => self::SORT_LOG,
            'actor' => $log->user?->full_name,
            'money' => null,
        ];
    }

    private static function fromPayment(Payment $payment): ?array
    {
        $at = $payment->paid_at ?? $payment->updated_at ?? $payment->created_at;
        if (! $at instanceof CarbonInterface) {
            return null;
        }

        $amt = number_format((float) $payment->amount, 0, ',', '.').' ₫';
        $method = self::paymentMethodLabel((string) $payment->method);
        $pst = strtolower((string) $payment->status);
        $stLabel = match ($pst) {
            'paid' => 'Đã thanh toán',
            'pending' => 'Chờ thanh toán',
            'partial' => 'Thanh toán một phần',
            'failed' => 'Thất bại',
            default => ucfirst((string) $payment->status),
        };

        $detailParts = [$method];
        $detailParts[] = 'Trạng thái: '.$pst;
        if ($payment->transaction_id) {
            $detailParts[] = 'GD: '.$payment->transaction_id;
        }

        return [
            'kind' => 'payment',
            'badge' => 'Thanh toán',
            'badge_class' => $pst === 'paid' ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : 'bg-secondary-subtle text-secondary-emphasis border',
            'title' => $stLabel.' '.$amt,
            'detail' => implode(' · ', $detailParts),
            'at' => $at->copy()->timezone(config('app.timezone')),
            'sort' => self::SORT_PAYMENT,
            'actor' => null,
            'money' => (float) $payment->amount,
        ];
    }

    private static function fromBookingService(BookingService $bs): array
    {
        $name = trim((string) ($bs->service?->name ?? 'Dịch vụ #' . (string) ($bs->service_id ?? '')));
        $qty = (int) $bs->quantity;
        $total = round((float) $bs->price * $qty, 2);
        $money = number_format($total, 0, ',', '.').' ₫';

        return [
            'kind' => 'service',
            'badge' => 'Dịch vụ',
            'badge_class' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
            'title' => $name.' × '.$qty,
            'detail' => 'Thành tiền: '.$money,
            'at' => self::normalizeCarbon($bs->updated_at ?? $bs->created_at ?? now()),
            'sort' => self::SORT_SERVICE,
            'actor' => null,
            'money' => $total,
        ];
    }

    private static function fromSurcharge(BookingSurcharge $s): array
    {
        $amt = number_format((float) $s->amount, 0, ',', '.').' ₫';

        return [
            'kind' => 'surcharge',
            'badge' => 'Phụ phí',
            'badge_class' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'title' => (string) $s->reason,
            'detail' => 'Số tiền: '.$amt,
            'at' => self::normalizeCarbon($s->created_at ?? $s->updated_at ?? now()),
            'sort' => self::SORT_SURCHARGE,
            'actor' => null,
            'money' => (float) $s->amount,
        ];
    }

    private static function fromRoomChange(RoomChangeHistory $h): array
    {
        $from = optional($h->fromRoom)->displayLabel() ?? ('#'.$h->from_room_id);
        $to = optional($h->toRoom)->displayLabel() ?? ('#'.$h->to_room_id);
        $by = trim((string) ($h->changedBy?->full_name ?? ''));
        $priceDiff = (float) ($h->price_difference ?? 0);

        $detailParts = array_filter([
            trim((string) ($h->reason ?? '')),
            $h->change_type !== null ? 'Loại: '.$h->change_type_label : null,
            abs($priceDiff) > 0.009
                ? ($priceDiff > 0
                    ? 'Khách trả thêm: -'.number_format(abs($priceDiff), 0, ',', '.').' ₫'
                    : 'Khách được hoàn: +'.number_format(abs($priceDiff), 0, ',', '.').' ₫')
                : 'Chênh lệch: 0 ₫',
            ! is_null($h->remaining_nights) ? 'Đêm còn lại: '.(int) $h->remaining_nights : null,
            $by !== '' ? 'Người xử lý: '.$by : null,
        ], static fn ($x) => (string) $x !== '');

        return [
            'kind' => 'room_change',
            'badge' => 'Đổi phòng',
            'badge_class' => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
            'title' => $from.' → '.$to,
            'detail' => $detailParts === [] ? null : implode(' — ', $detailParts),
            'at' => self::normalizeCarbon($h->changed_at ?? $h->created_at ?? now()),
            'sort' => self::SORT_ROOM_CHANGE,
            'actor' => $by !== '' ? $by : null,
            'money' => isset($h->price_difference) ? (float) $h->price_difference : null,
        ];
    }

    private static function fromRefund(RefundLog $r): array
    {
        $at = self::normalizeCarbon($r->refunded_at ?? $r->updated_at ?? $r->created_at ?? now());
        $detailRaw = implode(' — ', array_filter([
            isset($r->refund_type) && $r->refund_type !== '' ? 'Loại: '.(string) $r->refund_type : null,
            trim((string) ($r->reason ?? '')),
            $r->processor?->full_name ? 'Xử lý: '.$r->processor->full_name : null,
        ], static fn ($x) => (string) $x !== ''));
        $detailTrim = trim($detailRaw);

        return [
            'kind' => 'refund',
            'badge' => 'Hoàn tiền',
            'badge_class' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
            'title' => number_format((float) $r->refund_amount, 0, ',', '.').' ₫',
            'detail' => $detailTrim !== '' ? $detailTrim : null,
            'at' => $at,
            'sort' => self::SORT_REFUND,
            'actor' => $r->processor?->full_name,
            'money' => -(float) $r->refund_amount,
        ];
    }

    private static function maybePushInvoice(?CarbonInterface $at, ?string $number, Collection $events): void
    {
        if ($at instanceof CarbonInterface) {
            $title = trim((string) ($number ?? '')) !== ''
                ? 'Hóa đơn '.$number
                : 'Hóa đơn ghi nhận';

            $events->push([
                'kind' => 'invoice',
                'badge' => 'Hóa đơn',
                'badge_class' => 'bg-light text-dark border',
                'title' => $title,
                'detail' => null,
                'at' => $at->copy()->timezone(config('app.timezone')),
                'sort' => self::SORT_PAYMENT + 5,
                'actor' => null,
                'money' => null,
            ]);
        }
    }

    private static function sorter(array $a, array $b): int
    {
        $ta = self::effectiveTs($a);
        $tb = self::effectiveTs($b);
        if ($ta !== $tb) {
            return $tb <=> $ta;
        }

        return ($b['sort'] ?? 0) <=> ($a['sort'] ?? 0);
    }

    private static function effectiveTs(array $e): float
    {
        $dt = self::normalizeCarbon($e['at'] ?? now());

        return (float) $dt->unix() + (($e['fraction'] ?? 0) / 1_000_000);
    }

    /**
     * Hơi lệch thời gian giữa các nguồn cùng lúc (log vs phụ phí) để cùng giây vẫn ổn định.
     */
    private static function normalizeCarbon(CarbonInterface $at): Carbon
    {
        $c = $at instanceof Carbon ? $at->copy() : Carbon::parse((string) $at);

        return $c->timezone(config('app.timezone'));
    }

    private static function statusLabel(string $code): string
    {
        $code = strtolower($code);

        return match ($code) {
            'new' => 'Tạo đơn',
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            'cancel_requested' => 'Yêu cầu hủy / hoàn tiền',
            'checked_in' => 'Đã nhận phòng',
            'checked_out' => 'Đã trả phòng',
            default => $code,
        };
    }

    private static function paymentMethodLabel(string $method): string
    {
        $method = strtolower($method);

        return match ($method) {
            'cash' => 'Tiền mặt',
            'vnpay' => 'VNPay',
            default => ucfirst($method),
        };
    }

    private static function placedViaLabel(?string $via): string
    {
        return match ($via) {
            Booking::PLACED_VIA_ADMIN => 'Kênh: Admin.',
            Booking::PLACED_VIA_CUSTOMER_WEB, null, '' => 'Kênh: Web khách.',
            default => 'Kênh: '.$via,
        };
    }
}
