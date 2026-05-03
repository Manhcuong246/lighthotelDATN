<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\RefundLog;
use App\Models\RefundRequest;
use App\Models\RoomBookedDate;
use App\Support\CancellationRefundPolicy;
use Illuminate\Support\Facades\DB;

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
        $b = CancellationRefundPolicy::refundBreakdown($booking, true);
        $secondsUntil = $b['seconds_until_check_in'];

        return [
            'percentage' => $b['refund_percentage'],
            'amount' => $b['refund_amount'],
            'eligible' => $b['refund_percentage'] > 0,
            'hours_left' => $secondsUntil / 3600,
        ];
    }

    public function getLatestRefundRequestForBooking(int $bookingId): ?RefundRequest
    {
        return RefundRequest::query()
            ->where('booking_id', $bookingId)
            ->latest('id')
            ->first();
    }

    /**
     * @return array{allowed: bool, message?: string, calc?: array, existing?: RefundRequest|null}
     */
    public function canCustomerRequestRefund(Booking $booking, int $actingUserId): array
    {
        if ((int) $booking->user_id !== (int) $actingUserId) {
            return ['allowed' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.'];
        }

        if ($booking->status !== 'confirmed') {
            return ['allowed' => false, 'message' => 'Chỉ đơn đã xác nhận mới có thể yêu cầu hoàn tiền.'];
        }

        if (! $booking->isPaymentRecordedPaid()) {
            return ['allowed' => false, 'message' => 'Đơn chưa thanh toán nên không thể yêu cầu hoàn tiền.'];
        }

        $existing = $this->getLatestRefundRequestForBooking((int) $booking->id);
        if ($existing && $existing->status === 'pending_refund') {
            return ['allowed' => false, 'message' => 'Đơn này đã có yêu cầu hoàn tiền đang chờ xử lý.', 'existing' => $existing];
        }
        if ($existing && $existing->status === 'refunded') {
            return ['allowed' => false, 'message' => 'Đơn này đã được hoàn tiền trước đó.', 'existing' => $existing];
        }

        $calc = $this->calculateRefund($booking);
        if (! $calc['eligible']) {
            return ['allowed' => false, 'message' => 'Đơn đã quá thời hạn hoàn tiền theo chính sách.', 'calc' => $calc, 'existing' => $existing];
        }

        return ['allowed' => true, 'calc' => $calc, 'existing' => $existing];
    }

    /**
     * Khách (đăng nhập hoặc link có chữ ký) gửi yêu cầu hoàn tiền / hủy có hoàn.
     *
     * @param  array{account_name: string, account_number: string, bank_name: string, qr_image?: string|null, note?: string|null}  $data
     * @return array{success: bool, message?: string}
     */
    public function submitCustomerRefundRequest(Booking $booking, int $actingUserId, array $data): array
    {
        $eligibility = $this->canCustomerRequestRefund($booking, $actingUserId);
        if (! ($eligibility['allowed'] ?? false)) {
            return ['success' => false, 'message' => $eligibility['message'] ?? 'Đơn chưa đủ điều kiện hoàn tiền.'];
        }

        /** @var array{percentage:int,amount:float,eligible:bool,hours_left:int} $calc */
        $calc = $eligibility['calc'];
        $existing = $eligibility['existing'] ?? null;

        DB::transaction(function () use ($booking, $actingUserId, $data, $calc, $existing) {
            $payload = [
                'booking_id' => $booking->id,
                'user_id' => $actingUserId,
                'account_name' => $data['account_name'],
                'account_number' => $data['account_number'],
                'bank_name' => $data['bank_name'],
                'qr_image' => $data['qr_image'] ?? null,
                'refund_percentage' => $calc['percentage'],
                'refund_amount' => $calc['amount'],
                'note' => $data['note'] ?? null,
                'status' => 'pending_refund',
                'admin_note' => null,
                'refund_proof_image' => null,
            ];

            if ($existing && $existing->status === 'rejected') {
                $existing->update($payload);
            } else {
                RefundRequest::create($payload);
            }

            $oldStatus = (string) $booking->status;
            $booking->update(['status' => 'cancel_requested']);

            if ($oldStatus !== 'cancel_requested') {
                BookingLog::create([
                    'booking_id' => $booking->id,
                    'old_status' => $oldStatus,
                    'new_status' => 'cancel_requested',
                    'changed_at' => now(),
                ]);
            }
        });

        return ['success' => true];
    }

    /**
     * Process Approve Refund
     */
    public function approveRefund(RefundRequest $refundRequest, array $data): bool
    {
        return DB::transaction(function () use ($refundRequest, $data) {
            if ($refundRequest->status !== 'pending_refund') {
                return false;
            }

            $booking = $refundRequest->booking;
            if (! $booking) {
                return false;
            }

            $oldStatus = (string) $booking->status;
            $bookingTotal = (float) ($booking->total_price ?? 0);
            $refundAmount = (float) ($refundRequest->refund_amount ?? 0);
            $isPartial = $refundAmount > 0 && $bookingTotal > 0 && $refundAmount < $bookingTotal;

            // 1. Update Refund Request
            $refundRequest->update([
                'status' => 'refunded',
                'admin_note' => $data['admin_note'] ?? null,
                'refund_proof_image' => $data['refund_proof_image'] ?? null,
            ]);

            // 2. Update Booking + payment flags
            $booking->update([
                'status' => 'cancelled',
                'payment_status' => $isPartial ? 'partial_refunded' : 'refunded',
                'cancelled_at' => now(),
                'cancellation_reason' => $refundRequest->note ?: 'Hủy đơn theo yêu cầu hoàn tiền.',
            ]);

            $latestPayment = $booking->latestPayment()->first();
            if ($latestPayment) {
                $latestPayment->update(['status' => 'refunded']);
            }

            // 3. RELEASE ROOM DATES - Logic quan trọng nhất
            RoomBookedDate::where('booking_id', $booking->id)->delete();

            // 4. Log
            BookingLog::create([
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => 'cancelled',
                'notes' => 'Admin duyệt hoàn tiền cho đơn.',
                'changed_at' => now(),
            ]);

            RefundLog::create([
                'booking_id' => $booking->id,
                'refund_amount' => $refundAmount,
                'refund_type' => $isPartial ? 'partial' : 'full',
                'reason' => $refundRequest->note ?: 'Hoàn tiền theo yêu cầu khách hàng',
                'processed_by' => auth()->id(),
                'refunded_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Process Reject Refund
     */
    public function rejectRefund(RefundRequest $refundRequest, string $reason): bool
    {
        return DB::transaction(function () use ($refundRequest, $reason) {
            if ($refundRequest->status !== 'pending_refund') {
                return false;
            }

            $booking = $refundRequest->booking;
            if (! $booking) {
                return false;
            }

            $oldStatus = (string) $booking->status;

            // 1. Update Refund Request
            $refundRequest->update([
                'status' => 'rejected',
                'admin_note' => $reason,
            ]);

            // 2. Restore Booking Status to confirmed
            $booking->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'cancelled_at' => null,
                'cancellation_reason' => null,
            ]);

            // 3. Log
            BookingLog::create([
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => 'confirmed',
                'notes' => 'Admin từ chối yêu cầu hoàn tiền.',
                'changed_at' => now(),
            ]);

            return true;
        });
    }
}
