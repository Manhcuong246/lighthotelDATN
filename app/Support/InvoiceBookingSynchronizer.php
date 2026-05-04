<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Đồng bộ toàn bộ dòng phòng + DV/phụ phí + tổng HĐ theo đơn (sau đổi phòng / chỉnh đơn).
 */
final class InvoiceBookingSynchronizer
{
    public static function syncFullFromBooking(Booking $booking): void
    {
        $booking->loadMissing('invoice');
        $invoice = $booking->invoice;
        if (! $invoice) {
            return;
        }

        DB::transaction(function () use ($booking, $invoice): void {
            $booking->loadMissing([
                'bookingRooms.room.roomType',
                'rooms',
                'room.roomType',
                'bookingServices.service',
                'surcharges.service',
                'roomChangeHistories.fromRoom',
                'roomChangeHistories.toRoom',
                'roomChangeHistories.changedBy',
            ]);

            $invoice->items()->where('item_type', 'room')->delete();

            $servicesAmount = (float) $booking->bookingServices->sum(
                static fn ($s) => (float) $s->price * (int) $s->quantity
            );
            $surchargesAmount = (float) $booking->surcharges->sum(
                static fn ($s) => (float) $s->amount
            );
            $couponDiscount = (float) ($booking->discount_amount ?? 0);
            $bookingTotal = (float) $booking->total_price;

            if ($booking->bookingRooms->isNotEmpty()) {
                $roomsSum = 0.0;
                foreach ($booking->bookingRooms as $br) {
                    $payload = self::buildInvoiceRoomLineFromBookingRoom($br);
                    InvoiceItem::create(array_merge([
                        'invoice_id' => $invoice->id,
                        'item_type' => 'room',
                    ], $payload));
                    $roomsSum += (float) $br->subtotal;
                }
                $invoice->room_amount = $roomsSum;
            } else {
                $nights = max(1, Carbon::parse($booking->check_in)->diffInDays($booking->check_out));
                $roomPart = max(0, $bookingTotal - $servicesAmount - $surchargesAmount + $couponDiscount);
                $roomLabel = $booking->rooms->isNotEmpty()
                    ? $booking->rooms->pluck('name')->filter()->implode(', ')
                    : ($booking->room?->name ?? 'Lưu trú');
                $typeName = $booking->room?->roomType?->name;
                $payload = self::buildInvoiceRoomLineLegacy($booking, $roomPart, $nights, $roomLabel, $typeName);
                InvoiceItem::create(array_merge([
                    'invoice_id' => $invoice->id,
                    'item_type' => 'room',
                ], $payload));
                $invoice->room_amount = $roomPart;
            }

            $invoice->save();

            $invoice->refresh();
            $invoice->load('booking');
            InvoiceExtrasSynchronizer::replaceExtrasFromBooking($invoice, false);

            $invoice->items()->where('item_type', 'room_change_note')->delete();
            foreach ($booking->roomChangeHistories->sortBy('changed_at')->values() as $h) {
                $delta = (float) ($h->price_difference ?? 0);
                $from = $h->fromRoom?->displayLabel() ?? ('#'.$h->from_room_id);
                $to = $h->toRoom?->displayLabel() ?? ('#'.$h->to_room_id);
                $by = $h->changedBy?->full_name ?? 'Hệ thống';
                $at = $h->changed_at?->format('d/m/Y H:i') ?? '—';
                $deltaLine = $delta > 0
                    ? '+'.number_format($delta, 0, ',', '.').' ₫ (phát sinh so với giá đã chốt trước đó)'
                    : ($delta < 0
                        ? '−'.number_format(abs($delta), 0, ',', '.').' ₫ (điều chỉnh giảm)'
                        : '0 ₫');

                $desc = "Đổi phòng (ghi chú — không cộng riêng vào tổng; đã phản ánh trong tiền phòng hiện tại)\n"
                    ."Thời điểm: {$at}\n"
                    ."{$from} → {$to}\n"
                    ."Chênh giá kỳ đổi: {$deltaLine}\n"
                    .'Lý do: '.trim((string) ($h->reason ?? '—'))."\n"
                    ."Người xử lý: {$by}";

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => 'room_change_note',
                    'description' => $desc,
                    'quantity' => 1,
                    'unit_price' => 0,
                    'total_price' => 0,
                    'guest_adults' => null,
                    'guest_children_6_11' => null,
                    'guest_children_0_5' => null,
                ]);
            }
        });
    }

    /**
     * @return array{description: string, quantity: int, unit_price: float, total_price: float, guest_adults: int, guest_children_6_11: int, guest_children_0_5: int}
     */
    private static function buildInvoiceRoomLineFromBookingRoom(BookingRoom $br): array
    {
        $n = max(1, (int) $br->nights);
        $room = $br->room;
        $typeName = $room?->roomType?->name;
        $adults = (int) $br->adults;
        $c611 = (int) $br->children_6_11;
        $c05 = (int) $br->children_0_5;

        $lines = [
            $room?->displayLabel() ?? $room?->name ?? ('Phòng #' . $br->room_id),
            'Loại phòng: ' . ($typeName ?? '—'),
            'Số đêm lưu trú: ' . $n,
            '',
            'Thành phần khách (theo từng phòng trên đơn):',
            '• Người lớn: ' . $adults,
            '• Trẻ em 6–11 tuổi: ' . $c611 . ' (tính vào occupancy / giá theo chính sách loại phòng)',
            '• Trẻ em 0–5 tuổi: ' . $c05 . ' (thường miễn phí lưu trú, vẫn tính vào sức chứa tối đa phòng)',
        ];

        return [
            'description' => implode("\n", $lines),
            'quantity' => $n,
            'unit_price' => round((float) $br->subtotal / $n, 2),
            'total_price' => (float) $br->subtotal,
            'guest_adults' => $adults,
            'guest_children_6_11' => $c611,
            'guest_children_0_5' => $c05,
        ];
    }

    /**
     * @return array{description: string, quantity: int, unit_price: float, total_price: float, guest_adults: ?int, guest_children_6_11: ?int, guest_children_0_5: ?int}
     */
    private static function buildInvoiceRoomLineLegacy(Booking $booking, float $roomPart, int $nights, string $roomLabel, ?string $typeName): array
    {
        $lines = [
            'Lưu trú (một dòng gộp — đơn không chi tiết từng phòng): ' . $roomLabel,
            'Loại phòng: ' . ($typeName ?? '—'),
            'Số đêm lưu trú: ' . $nights,
            '',
            'Thành phần khách:',
        ];

        $guestAdults = null;
        $guestC6 = null;
        $guestC0 = null;

        if ($booking->adults !== null && $booking->adults !== '') {
            $guestAdults = (int) $booking->adults;
            $lines[] = '• Người lớn: ' . $guestAdults;
        }
        if ($booking->children !== null && $booking->children !== '') {
            $lines[] = '• Trẻ em (tổng ghi trên đơn — không phân tách 0–5 và 6–11 trong dữ liệu cũ): ' . (int) $booking->children;
        }
        if ($guestAdults === null && $booking->guests !== null && $booking->guests !== '') {
            $lines[] = '• Tổng số khách ghi trên đơn: ' . (int) $booking->guests;
        }
        if (count($lines) <= 5) {
            $lines[] = '• Không có chi tiết NL / trẻ 0–5 / trẻ 6–11 trên đơn — đơn mới đa phòng sẽ hiển thị đủ từng phòng.';
        }

        return [
            'description' => implode("\n", $lines),
            'quantity' => $nights,
            'unit_price' => round($roomPart / $nights, 2),
            'total_price' => $roomPart,
            'guest_adults' => $guestAdults,
            'guest_children_6_11' => $guestC6,
            'guest_children_0_5' => $guestC0,
        ];
    }
}
