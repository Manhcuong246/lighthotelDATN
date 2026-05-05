<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\RefundRequest;
use App\Models\RefundLog;
use App\Models\Room;
use App\Models\RoomBookedDate;
use App\Support\CancellationRefundPolicy;
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
     * @param bool $isAdminInitiated Cho phép hủy sau mốc nhận phòng (hoàn 0%) — chỉ admin/nội bộ.
     * @return array
     */
    public function cancelBooking(int $bookingId, ?string $reason = null, ?int $processedBy = null, bool $isAdminInitiated = false): array
    {
        try {
            DB::beginTransaction();

            // Find booking
            $booking = Booking::find($bookingId);
            if (!$booking) {
                throw new Exception('Booking không tồn tại.');
            }

            $isPaymentRecordedPaidEarly = $booking->isPaymentRecordedPaid();
            if (! $isAdminInitiated && ! CancellationRefundPolicy::customerWebCancelAllowed($booking, $isPaymentRecordedPaidEarly)) {
                throw new Exception('Theo chính sách, đơn đã qua thời gian nhận phòng nên không thể tự hủy trên web. Vui lòng liên hệ lễ tân.');
            }

            // Check if already cancelled
            if ($booking->status === 'cancelled') {
                throw new Exception('Booking này đã bị hủy trước đó.');
            }
            if ($booking->status === 'cancel_requested') {
                throw new Exception('Booking này đang có yêu cầu hoàn tiền chờ xử lý.');
            }
            if (in_array((string) $booking->status, ['checked_in', 'checked_out', 'completed'], true)) {
                throw new Exception('Booking đã ở giai đoạn lưu trú/hoàn thành, không thể hủy theo luồng này.');
            }
            if (RefundRequest::query()->where('booking_id', $booking->id)->where('status', 'pending_refund')->exists()) {
                throw new Exception('Booking này đang có yêu cầu hoàn tiền chờ xử lý.');
            }

            $isPaymentRecordedPaid = $booking->isPaymentRecordedPaid();

            // Chưa thanh toán: xóa hẳn đơn — không lưu trạng thái hủy (giảm rác DB / spam).
            if (! $isPaymentRecordedPaid) {
                $obliterator = app(UnpaidBookingObliterateService::class);
                if ($obliterator->obliterateIfUnpaid($booking)) {
                    DB::commit();

                    Log::info("Booking {$bookingId} obliterated on cancel (unpaid)");

                    return [
                        'success' => true,
                        'obliterated' => true,
                        'message' => 'Hủy thành công. Đơn chưa thanh toán đã được gỡ khỏi hệ thống — không lưu bản ghi hủy, phòng được mở lại ngay.',
                        'refund_amount' => 0,
                        'refund_type' => 'none',
                        'booking' => null,
                    ];
                }

                throw new Exception('Không thể gỡ đơn — vui lòng thử lại.');
            }

            // Calculate refund based on timing
            $refundResult = $this->calculateRefund($booking, $isPaymentRecordedPaid);

            $paymentStatus = $isPaymentRecordedPaid
                ? match ($refundResult['refund_type']) {
                    'full' => 'refunded',
                    'partial' => 'partial_refunded',
                    default => 'paid',
                }
                : 'pending';

            // Update booking status + giải phóng lịch phòng (tránh ghost block)
            $booking->update([
                'status' => 'cancelled',
                'payment_status' => $paymentStatus,
                'cancelled_at' => Carbon::now(),
                'cancellation_reason' => $reason,
            ]);

            RoomBookedDate::where('booking_id', $booking->id)->delete();

            $released = $booking->bookingRooms()
                ->whereNotNull('room_id')
                ->pluck('room_id')
                ->map(static fn ($id) => (int) $id)
                ->filter(static fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();
            if ($released !== []) {
                Room::whereIn('id', $released)->update(['status' => 'available']);
            }

            $latestPayment = $booking->latestPayment()->first();
            if ($latestPayment) {
                if (! $isPaymentRecordedPaid && $latestPayment->status === 'pending') {
                    $latestPayment->update(['status' => 'failed']);
                }

                if ($isPaymentRecordedPaid && $refundResult['refund_amount'] > 0) {
                    $latestPayment->update(['status' => 'refunded']);
                }
            }

            // Chỉ tạo log hoàn tiền khi đơn đã ghi nhận thanh toán và có số tiền hoàn > 0.
            if ($isPaymentRecordedPaid && $refundResult['refund_amount'] > 0) {
                RefundLog::create([
                    'booking_id' => $booking->id,
                    'refund_amount' => $refundResult['refund_amount'],
                    'refund_type' => $refundResult['refund_type'],
                    'reason' => $reason ?? 'Hủy booking theo yêu cầu',
                    'processed_by' => $processedBy,
                    'refunded_at' => Carbon::now(),
                ]);
            }

            DB::commit();

            Log::info("Booking {$bookingId} cancelled successfully", [
                'refund_amount' => $refundResult['refund_amount'],
                'refund_type' => $refundResult['refund_type'],
                'processed_by' => $processedBy,
            ]);

            return [
                'success' => true,
                'obliterated' => false,
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
    private function calculateRefund(Booking $booking, bool $isPaymentRecordedPaid): array
    {
        if (! $isPaymentRecordedPaid) {
            return [
                'refund_amount' => 0,
                'refund_type' => 'none',
                'message' => 'Hủy thành công. Đơn chưa thanh toán nên không phát sinh hoàn tiền.',
            ];
        }

        $b = CancellationRefundPolicy::refundBreakdown($booking, true);
        $totalPrice = (float) $booking->total_price;

        if ($b['refund_type'] === 'full') {
            return [
                'refund_amount' => $b['refund_amount'],
                'refund_type' => 'full',
                'message' => 'Hủy thành công. Hoàn lại 100% số tiền (' . number_format($totalPrice, 0, ',', '.') . ' ₫).',
            ];
        }

        if ($b['refund_type'] === 'partial') {
            $partialRefund = $b['refund_amount'];

            return [
                'refund_amount' => $partialRefund,
                'refund_type' => 'partial',
                'message' => 'Hủy thành công. Hoàn lại 50% số tiền (' . number_format($partialRefund, 0, ',', '.') . ' ₫) do hủy trong vòng 24 giờ trước nhận phòng.',
            ];
        }

        return [
            'refund_amount' => 0,
            'refund_type' => 'none',
            'message' => 'Hủy thành công. Không hoàn tiền do đã qua thời gian nhận phòng.',
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
        $checkInDate = CancellationRefundPolicy::resolvePolicyCheckIn($booking);
        $secondsUntil = CancellationRefundPolicy::secondsUntilPolicyCheckIn($booking);
        $hoursUntilCheckIn = $secondsUntil / 3600;
        $isPaymentRecordedPaid = $booking->isPaymentRecordedPaid();

        $policy = [
            'current_time' => $now->format('d/m/Y H:i'),
            'check_in_time' => $checkInDate->format('d/m/Y H:i'),
            'hours_until_check_in' => $hoursUntilCheckIn,
            'can_cancel' => true,
            'refund_percentage' => 0,
            'refund_amount' => 0,
            'policy_text' => '',
        ];

        if (! $isPaymentRecordedPaid) {
            $policy['can_cancel'] = true;
            $policy['policy_text'] = 'Đơn chưa thanh toán. Bạn có thể hủy đơn và sẽ không có giao dịch hoàn tiền.';

            return $policy;
        }

        $b = CancellationRefundPolicy::refundBreakdown($booking, true);
        $policy['can_cancel'] = CancellationRefundPolicy::customerWebCancelAllowed($booking, true);
        $policy['refund_percentage'] = $b['refund_percentage'];
        $policy['refund_amount'] = $b['refund_amount'];

        if ($b['refund_percentage'] === 100) {
            $policy['policy_text'] = 'Hoàn 100% tiền nếu hủy trước hơn 24 giờ so với thời gian nhận phòng.';
        } elseif ($b['refund_percentage'] === 50) {
            $policy['policy_text'] = 'Hoàn 50% tiền nếu hủy trong vòng 24 giờ trước thời gian nhận phòng.';
        } else {
            $policy['policy_text'] = 'Không hoàn tiền nếu hủy sau thời gian nhận phòng.';
        }

        return $policy;
    }
}
