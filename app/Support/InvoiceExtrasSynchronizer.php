<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Đồng bộ dịch vụ đặt kèm & phụ phí từ đơn lên hóa đơn (dòng chi tiết + tổng tiền).
 */
final class InvoiceExtrasSynchronizer
{
    /**
     * Xóa các dòng service/surcharge/adjustment cũ trên HĐ và ghi lại theo đơn hiện tại.
     *
     * @param  bool  $wrapInTransaction  False khi đã đứng trong transaction lớn hơn (vd. đồng bộ cả dòng phòng).
     */
    public static function replaceExtrasFromBooking(Invoice $invoice, bool $wrapInTransaction = true): void
    {
        $invoice->loadMissing([
            'booking.bookingServices.service',
            'booking.surcharges.service',
            'booking.bookingRooms',
        ]);
        $booking = $invoice->booking;
        if (! $booking) {
            throw new \InvalidArgumentException('Không có đơn đặt gắn với hóa đơn.');
        }

        $runner = static function () use ($invoice, $booking): void {
            $servicesAmount = (float) $booking->bookingServices->sum(
                static fn ($s) => (float) $s->price * (int) $s->quantity
            );
            $surchargesAmount = (float) $booking->surcharges->sum(
                static fn ($s) => (float) $s->amount
            );
            $couponDiscount = (float) ($booking->discount_amount ?? 0);
            $bookingTotal = (float) $booking->total_price;

            $invoice->items()->whereIn('item_type', ['service', 'surcharge', 'adjustment'])->delete();

            self::appendServiceItems($invoice, $booking);
            self::appendSurchargeItems($invoice, $booking);

            $invoice->services_amount = $servicesAmount;
            $invoice->surcharges_amount = $surchargesAmount;

            self::reconcileAdjustment($invoice, $servicesAmount, $surchargesAmount, $couponDiscount, $bookingTotal);

            $invoice->total_amount = $bookingTotal
                - (float) $invoice->discount_amount
                + (float) $invoice->tax_amount;

            $paid = (float) $invoice->paid_amount;
            $total = (float) $invoice->total_amount;
            if ($paid <= 0.009) {
                $invoice->status = 'pending';
            } elseif ($paid + 0.009 >= $total) {
                $invoice->status = 'paid';
            } else {
                $invoice->status = 'partially_paid';
            }

            $invoice->save();
        };

        if ($wrapInTransaction) {
            DB::transaction($runner);
        } else {
            $runner();
        }
    }

    public static function appendServiceItems(Invoice $invoice, Booking $booking): void
    {
        foreach ($booking->bookingServices as $service) {
            $svcName = $service->service?->name ?? ('Dịch vụ #' . $service->service_id);
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'item_type' => 'service',
                'description' => Str::limit('Dịch vụ đặt kèm: ' . $svcName, 250),
                'quantity' => $service->quantity,
                'unit_price' => $service->price,
                'total_price' => (float) $service->price * (int) $service->quantity,
            ]);
        }
    }

    public static function appendSurchargeItems(Invoice $invoice, Booking $booking): void
    {
        foreach ($booking->surcharges as $sc) {
            $desc = 'Phụ phí: ' . $sc->reason;
            if ($sc->service) {
                $desc .= ' [từng gắn danh mục: ' . $sc->service->name . ' × ' . (int) ($sc->quantity ?? 1) . ']';
            }
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'item_type' => 'surcharge',
                'description' => Str::limit($desc, 250),
                'quantity' => max(1, (int) ($sc->quantity ?? 1)),
                'unit_price' => round((float) $sc->amount / max(1, (int) ($sc->quantity ?? 1)), 2),
                'total_price' => (float) $sc->amount,
            ]);
        }
    }

    public static function reconcileAdjustment(
        Invoice $invoice,
        float $servicesAmount,
        float $surchargesAmount,
        float $couponDiscount,
        float $bookingTotal
    ): void {
        $computedSubtotal = (float) $invoice->room_amount + $servicesAmount + $surchargesAmount - $couponDiscount;
        $diff = round($bookingTotal - $computedSubtotal, 2);
        if (abs($diff) >= 0.01) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'item_type' => 'adjustment',
                'description' => 'Điều chỉnh (làm tròn / chỉnh tổng đơn so với chi tiết)',
                'quantity' => 1,
                'unit_price' => $diff,
                'total_price' => $diff,
            ]);
        }
    }
}
