<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\HotelInfo;

final class BookingInvoiceViewData
{
    /**
     * Quy tắc xem hóa đơn / biên lai (khách và admin): sau checkout + đã thanh toán.
     */
    public static function customerCanView(Booking $booking): bool
    {
        return $booking->isPaidAndCheckedOutForInvoice();
    }

    /**
     * Khách (portal / tài khoản): xem biên lai thanh toán trên web khi đã thu tiền, chưa hủy.
     * Không yêu cầu checkout — dùng sau VNPay; sau checkout vẫn hợp lệ.
     */
    public static function guestCanViewInvoiceSheet(Booking $booking): bool
    {
        if (in_array($booking->status, ['cancelled', 'cancel_requested'], true)) {
            return false;
        }

        return $booking->isPaymentRecordedPaid();
    }

    /**
     * @return array{booking: Booking, hotel: ?HotelInfo, roomLines: list<array<string, mixed>>, servicesTotal: float, surchargesTotal: float, discountAmount: float, invoiceNo: string}
     */
    public static function make(Booking $booking): array
    {
        $booking->load([
            'user',
            'room.roomType',
            'rooms.roomType',
            'bookingGuests',
            'bookingRooms.room.roomType',
            'bookingServices.service',
            'surcharges.service',
            'roomChangeHistories.fromRoom.roomType',
            'roomChangeHistories.toRoom.roomType',
            'roomChangeHistories.changedBy',
            'latestPayment',
            'invoice',
            'payments',
        ]);

        $hotel = HotelInfo::first();

        $servicesTotal = (float) $booking->bookingServices->sum(
            static fn ($bs) => (float) $bs->price * (int) $bs->quantity
        );
        $surchargesTotal = (float) $booking->surcharges->sum(
            static fn ($s) => (float) $s->amount
        );
        $discountAmount = (float) ($booking->discount_amount ?? 0);

        $roomLines = [];
        if ($booking->bookingRooms->isNotEmpty()) {
            foreach ($booking->bookingRooms as $br) {
                $room = $br->room;
                $occ = $booking->occupancyDisplayCountsForBookingRoom($br);
                $sub = (float) $br->subtotal;
                $ppn = (float) $br->price_per_night;
                $nightsBilled = $ppn > 0.009 ? max(1, (int) round($sub / $ppn)) : max(1, (int) $br->nights);
                $nightsCalendar = (int) $br->nights;

                $roomLines[] = [
                    'label' => $room?->name ?? 'Phòng #' . $br->room_id,
                    'detail' => $room?->roomType?->name,
                    'nights' => $nightsBilled,
                    'nights_calendar' => $nightsCalendar,
                    'unit_price' => $ppn,
                    'quantity_note' => sprintf(
                        '%d NL, %d trẻ 6–11t, %d trẻ 0–5t (theo nhận phòng)',
                        $occ['adults'],
                        $occ['children_6_11'],
                        $occ['children_0_5']
                    ),
                    'line_total' => $sub,
                ];
            }
        } elseif ($booking->room) {
            $legacyRoom = (float) ($booking->total_price ?? 0) - $servicesTotal - $surchargesTotal + $discountAmount;
            $roomLines[] = [
                'label' => $booking->room->name ?? 'Phòng',
                'detail' => $booking->room->roomType?->name,
                'nights' => null,
                'unit_price' => null,
                'quantity_note' => $booking->guests ? sprintf('%d khách', (int) $booking->guests) : null,
                'line_total' => max(0, $legacyRoom),
            ];
        }

        $invoiceNo = 'HĐ-' . $booking->created_at?->format('Ymd') . '-' . str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT);

        $roomChangeLines = $booking->roomChangeHistories
            ->sortBy('changed_at')
            ->values()
            ->map(static function ($h): array {
                $from = $h->fromRoom?->displayLabel() ?? ('#'.$h->from_room_id);
                $to = $h->toRoom?->displayLabel() ?? ('#'.$h->to_room_id);
                $delta = (float) ($h->price_difference ?? 0);

                return [
                    'at' => $h->changed_at,
                    'from_room' => $from,
                    'to_room' => $to,
                    'reason' => (string) ($h->reason ?? ''),
                    'changed_by' => (string) ($h->changedBy?->full_name ?? 'Hệ thống'),
                    'delta' => $delta,
                ];
            })
            ->all();

        $roomChangeDeltaTotal = (float) collect($roomChangeLines)->sum('delta');

        $roomSubtotal = $booking->bookingRooms->isNotEmpty()
            ? (float) $booking->bookingRooms->sum('subtotal')
            : (float) collect($roomLines)->sum('line_total');

        $extrasSubtotal = $servicesTotal + $surchargesTotal;

        $totalPaidFromPayments = (float) $booking->payments
            ->where('status', 'paid')
            ->sum('amount');

        $bookingTotal = (float) $booking->total_price;
        $balanceDue = max(0, round($bookingTotal - $totalPaidFromPayments, 2));

        $invoiceRemaining = null;
        if ($booking->invoice) {
            $inv = $booking->invoice;
            $invoiceRemaining = max(0, round((float) $inv->total_amount - (float) $inv->paid_amount, 2));
        }

        return compact(
            'booking',
            'hotel',
            'roomLines',
            'servicesTotal',
            'surchargesTotal',
            'discountAmount',
            'invoiceNo',
            'roomSubtotal',
            'roomChangeLines',
            'roomChangeDeltaTotal',
            'extrasSubtotal',
            'totalPaidFromPayments',
            'balanceDue',
            'invoiceRemaining'
        );
    }
}
