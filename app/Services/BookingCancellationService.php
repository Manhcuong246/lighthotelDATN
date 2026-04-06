<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\RefundLog;
use App\Models\RoomBookedDate;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingCancellationService
{
    /**
     * Cancel a booking and calculate refund based on timing.
     *
     * @param int $bookingId
     * @param string|null $reason
     * @param int|null $processedBy
     * @return array
     */
    public function cancelBooking(int $bookingId, ?string $reason = null, ?int $processedBy = null): array
    {
        try {
            DB::beginTransaction();

            // Find booking
            $booking = Booking::find($bookingId);
            if (!$booking) {
                throw new Exception('Booking không tồn tại.');
            }

            // Check if already cancelled
            if ($booking->status === 'cancelled') {
                throw new Exception('Booking này đã bị hủy trước đó.');
            }

            // Calculate refund based on timing
            $refundResult = $this->calculateRefund($booking);

            $paymentStatus = match ($refundResult['refund_type']) {
                'full' => 'refunded',
                'partial' => 'partial_refunded',
                default => $booking->payment_status ?? 'pending',
            };

            // Update booking status + giải phóng lịch phòng (tránh ghost block)
            $booking->update([
                'status' => 'cancelled',
                'payment_status' => $paymentStatus,
                'cancelled_at' => Carbon::now(),
                'cancellation_reason' => $reason,
            ]);

            RoomBookedDate::where('booking_id', $booking->id)->delete();

            if ($booking->payment && $booking->payment->status === 'paid' && $refundResult['refund_type'] === 'full') {
                $booking->payment->update(['status' => 'refunded']);
            }

            // Create refund log
            RefundLog::create([
                'booking_id' => $booking->id,
                'refund_amount' => $refundResult['refund_amount'],
                'refund_type' => $refundResult['refund_type'],
                'reason' => $reason ?? 'Hủy booking theo yêu cầu',
                'processed_by' => $processedBy,
                'refunded_at' => Carbon::now(),
            ]);

            DB::commit();

            Log::info("Booking {$bookingId} cancelled successfully", [
                'refund_amount' => $refundResult['refund_amount'],
                'refund_type' => $refundResult['refund_type'],
                'processed_by' => $processedBy,
            ]);

            return [
                'success' => true,
                'message' => $refundResult['message'],
                'refund_amount' => $refundResult['refund_amount'],
                'refund_type' => $refundResult['refund_type'],
                'booking' => $booking->fresh(),
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to cancel booking {$bookingId}: " . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'refund_amount' => 0,
                'refund_type' => 'none',
            ];
        }
    }

    /**
     * Calculate refund amount based on current time vs check-in time.
     *
     * @param Booking $booking
     * @return array
     */
    private function calculateRefund(Booking $booking): array
    {
        $now = Carbon::now();
        $checkInAt = $this->resolvePolicyCheckIn($booking);

        // Calculate hours difference
        $hoursUntilCheckIn = $now->diffInHours($checkInAt, false);

        // Case 1: More than 24 hours before check-in
        if ($hoursUntilCheckIn > 24) {
            return [
                'refund_amount' => $booking->total_price,
                'refund_type' => 'full',
                'message' => 'Hủy thành công. Hoàn lại 100% số tiền (' . number_format($booking->total_price, 0, ',', '.') . ' ₫).',
            ];
        }

        // Case 2: Within 24 hours before check-in
        if ($hoursUntilCheckIn > 0 && $hoursUntilCheckIn <= 24) {
            $partialRefund = $booking->total_price * 0.5;
            return [
                'refund_amount' => $partialRefund,
                'refund_type' => 'partial',
                'message' => 'Hủy thành công. Hoàn lại 50% số tiền (' . number_format($partialRefund, 0, ',', '.') . ' ₫) do hủy trong vòng 24 giờ trước nhận phòng.',
            ];
        }

        // Case 3: On or after check-in time
        if ($hoursUntilCheckIn <= 0) {
            return [
                'refund_amount' => 0,
                'refund_type' => 'none',
                'message' => 'Hủy thành công. Không hoàn tiền do đã qua thời gian nhận phòng.',
            ];
        }

        // Fallback
        return [
            'refund_amount' => 0,
            'refund_type' => 'none',
            'message' => 'Không thể xác định chính sách hoàn tiền.',
        ];
    }

    /**
     * Get cancellation policy for a booking.
     *
     * @param Booking $booking
     * @return array
     */
    public function getCancellationPolicy(Booking $booking): array
    {
        $now = Carbon::now();
        $checkInDate = $this->resolvePolicyCheckIn($booking);
        $hoursUntilCheckIn = $now->diffInHours($checkInDate, false);

        $policy = [
            'current_time' => $now->format('d/m/Y H:i'),
            'check_in_time' => $checkInDate->format('d/m/Y H:i'),
            'hours_until_check_in' => $hoursUntilCheckIn,
            'can_cancel' => true,
            'refund_percentage' => 0,
            'refund_amount' => 0,
            'policy_text' => '',
        ];

        if ($hoursUntilCheckIn > 24) {
            $policy['refund_percentage'] = 100;
            $policy['refund_amount'] = $booking->total_price;
            $policy['policy_text'] = 'Hoàn 100% tiền nếu hủy trước hơn 24 giờ so với thời gian nhận phòng.';
        } elseif ($hoursUntilCheckIn > 0 && $hoursUntilCheckIn <= 24) {
            $policy['refund_percentage'] = 50;
            $policy['refund_amount'] = $booking->total_price * 0.5;
            $policy['policy_text'] = 'Hoàn 50% tiền nếu hủy trong vòng 24 giờ trước thời gian nhận phòng.';
        } else {
            $policy['can_cancel'] = false;
            $policy['policy_text'] = 'Không hoàn tiền nếu hủy sau thời gian nhận phòng.';
        }

        return $policy;
    }

    /**
     * Cùng quy tắc với RefundService: mốc nhận phòng 14:00; ưu tiên check_in_date nếu có.
     */
    private function resolvePolicyCheckIn(Booking $booking): Carbon
    {
        $base = $booking->check_in_date ?? $booking->check_in;

        return Carbon::parse($base)->setTime(14, 0, 0);
    }
}
