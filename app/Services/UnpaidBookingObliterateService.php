<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingPaymentIntent;
use App\Models\DamageReport;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RefundLog;
use App\Models\RefundRequest;
use App\Models\Review;
use App\Models\RoomBookedDate;
use App\Models\RoomChange;
use App\Models\RoomChangeHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Xóa hẳn đơn chưa có thanh toán thành công — giảm rác DB khi spam/hủy hàng loạt;
 * đơn đã có payment status = paid vẫn giữ để đối soát / hoàn tiền.
 */
class UnpaidBookingObliterateService
{
    /**
     * @return bool True nếu đã xóa hẳn; false nếu đơn có thanh toán paid (không xóa).
     */
    public function obliterateIfUnpaid(Booking $booking): bool
    {
        if ($this->mustRetainRecord($booking)) {
            return false;
        }

        $booking->loadMissing(['invoice']);

        $bid = (int) $booking->id;

        DB::transaction(function () use ($booking, $bid): void {
            $id = $bid;

            RoomBookedDate::query()->where('booking_id', $id)->delete();

            DamageReport::query()->where('booking_id', $id)->delete();

            RoomChangeHistory::query()->where('booking_id', $id)->delete();
            RoomChange::query()->where('booking_id', $id)->delete();

            RefundLog::query()->where('booking_id', $id)->delete();
            RefundRequest::query()->where('booking_id', $id)->delete();

            Review::query()->where('booking_id', $id)->delete();

            if ($booking->invoice) {
                $booking->invoice->forceDelete();
            } else {
                Invoice::withTrashed()->where('booking_id', $id)->forceDelete();
            }

            Payment::query()->where('booking_id', $id)->delete();

            $booking->logs()->delete();
            $booking->bookingServices()->delete();
            $booking->surcharges()->delete();
            $booking->bookingGuests()->delete();
            $booking->guests()->delete();
            $booking->bookingRooms()->delete();

            BookingPaymentIntent::query()->where('booking_id', $id)->delete();

            $booking->forceDelete();
        });

        Log::info('Unpaid booking obliterated', ['booking_id' => $bid]);

        return true;
    }

    private function mustRetainRecord(Booking $booking): bool
    {
        if ($booking->isPaymentRecordedPaid()) {
            return true;
        }

        return Payment::query()
            ->where('booking_id', $booking->id)
            ->where('status', 'paid')
            ->exists();
    }
}
