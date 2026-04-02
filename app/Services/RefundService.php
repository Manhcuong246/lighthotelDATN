<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\RefundRequest;
use App\Models\RoomBookedDate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RefundService
{
    /**
     * Calculate refund percentage and amount based on how many hours before check-in.
     * Logic: >24h (100%), 0-24h (50%), <0h/quá hạn (0%).
     *
     * @param Booking $booking
     * @return array
     */
    public function calculateRefund(Booking $booking): array
    {
        // Giả sử giờ check-in mặc định là 14:00 (2 PM)
        $checkInTime = Carbon::parse($booking->check_in)->setTime(14, 0, 0);
        $now = Carbon::now();

        $hoursDifference = $now->diffInHours($checkInTime, false);

        $percentage = 0;
        // Case 1: More than 24 hours before check-in → Full refund (100%)
        if ($hoursDifference > 24) {
            $percentage = 100;
        }
        // Case 2: Between 0 and 24 hours before check-in → Partial refund (50%)
        elseif ($hoursDifference > 0 && $hoursDifference <= 24) {
            $percentage = 50;
        }
        // Case 3: After check-in time → No refund (0%)

        $amount = ($booking->total_price * $percentage) / 100;

        return [
            'percentage' => $percentage,
            'amount' => $amount,
            'eligible' => $percentage > 0,
            'hours_left' => $hoursDifference
        ];
    }

    /**
     * Process Approve Refund
     */
    public function approveRefund(RefundRequest $refundRequest, array $data): bool
    {
        return DB::transaction(function () use ($refundRequest, $data) {
            $booking = $refundRequest->booking;

            // 1. Update Refund Request
            $refundRequest->update([
                'status' => 'refunded',
                'admin_note' => $data['admin_note'] ?? null,
                'refund_proof_image' => $data['refund_proof_image'] ?? null,
            ]);

            // 2. Update Booking Status
            $booking->update(['status' => 'cancelled']);

            // 3. RELEASE ROOM DATES - Logic quan trọng nhất
            RoomBookedDate::where('booking_id', $booking->id)->delete();

            // 4. Log
            \App\Models\BookingLog::create([
                'booking_id' => $booking->id,
                'old_status' => 'cancel_requested',
                'new_status' => 'cancelled',
                'changed_at' => now(),
            ]);

            // 5. Send Email to User
            try {
                \Illuminate\Support\Facades\Mail::to($booking->user->email)->send(new \App\Mail\RefundProcessedMail($refundRequest));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Mail error: ' . $e->getMessage());
            }

            return true;
        });
    }

    /**
     * Process Reject Refund
     */
    public function rejectRefund(RefundRequest $refundRequest, string $reason): bool
    {
        return DB::transaction(function () use ($refundRequest, $reason) {
            $booking = $refundRequest->booking;

            // 1. Update Refund Request
            $refundRequest->update([
                'status' => 'rejected',
                'admin_note' => $reason,
            ]);

            // 2. Restore Booking Status to confirmed
            $booking->update(['status' => 'confirmed']);

            // 3. Log
            \App\Models\BookingLog::create([
                'booking_id' => $booking->id,
                'old_status' => 'cancel_requested',
                'new_status' => 'confirmed',
                'changed_at' => now(),
            ]);

            // 4. Send Email to User
            try {
                \Illuminate\Support\Facades\Mail::to($booking->user->email)->send(new \App\Mail\RefundProcessedMail($refundRequest));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Mail error: ' . $e->getMessage());
            }

            return true;
        });
    }
}
