<?php

namespace App\Services;

use App\Mail\BookingCancellationMail;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\HotelInfo;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingCancellationService
{
    public const ERR_FORBIDDEN = 'forbidden';

    public const ERR_INVALID_STATUS = 'invalid_status';

    public const ERR_NON_REFUNDABLE = 'non_refundable';

    public const ERR_PAST_CHECK_IN = 'past_check_in';

    /**
     * @return array{hours_until: float, penalty_percent: int, penalty_amount: float, eligible_amount: float, paid_amount: float, tier: string}
     */
    public function evaluatePolicy(Booking $booking): array
    {
        $booking->loadMissing(['rooms.roomType', 'payment']);
        $hotel = HotelInfo::query()->first();

        $checkInTime = $hotel?->default_check_in_time ?? config('booking.check_in_time', '14:00:00');
        if (is_string($checkInTime) && strlen($checkInTime) === 5) {
            $checkInTime .= ':00';
        }
        $checkInAt = Carbon::parse($booking->check_in)->setTimeFromTimeString((string) $checkInTime);

        $hoursUntil = now()->diffInHours($checkInAt, false);

        $freeH = (int) ($hotel?->cancel_free_hours ?? config('booking.cancel_free_hours'));
        $midLow = (int) ($hotel?->cancel_mid_hours_low ?? config('booking.cancel_mid_hours_low'));
        $midPct = (int) ($hotel?->cancel_penalty_mid_percent ?? config('booking.cancel_penalty_mid_percent'));
        $shortPct = (int) ($hotel?->cancel_penalty_short_percent ?? config('booking.cancel_penalty_short_percent'));

        $paidAmount = $booking->payment && $booking->payment->status === 'paid'
            ? (float) $booking->payment->amount
            : 0.0;

        $penaltyPercent = 0;
        $tier = 'free';

        if ($hoursUntil >= $freeH) {
            $penaltyPercent = 0;
            $tier = 'free';
        } elseif ($hoursUntil >= $midLow) {
            $penaltyPercent = $midPct;
            $tier = 'mid';
        } else {
            $penaltyPercent = $shortPct;
            $tier = 'short';
        }

        $penaltyAmount = round($paidAmount * ($penaltyPercent / 100), 2);
        $eligibleAmount = max(0, round($paidAmount - $penaltyAmount, 2));

        return [
            'hours_until' => (float) $hoursUntil,
            'penalty_percent' => $penaltyPercent,
            'penalty_amount' => $penaltyAmount,
            'eligible_amount' => $eligibleAmount,
            'paid_amount' => $paidAmount,
            'tier' => $tier,
        ];
    }

    public function isNonRefundableBooking(Booking $booking): bool
    {
        $booking->loadMissing('rooms.roomType');

        return $booking->rooms->contains(function ($room) {
            return (bool) ($room->roomType?->is_non_refundable);
        });
    }

    /**
     * @return array{ok: bool, error?: string, code?: string, booking?: Booking}
     */
    public function cancelByCustomer(Booking $booking, int $userId, ?string $reason = null): array
    {
        if ($booking->user_id !== $userId) {
            return ['ok' => false, 'error' => 'Bạn không có quyền hủy đơn này.', 'code' => self::ERR_FORBIDDEN];
        }

        if (! in_array($booking->status, ['pending', 'confirmed'], true)) {
            return ['ok' => false, 'error' => 'Không thể hủy đơn ở trạng thái hiện tại.', 'code' => self::ERR_INVALID_STATUS];
        }

        $booking->load(['rooms.roomType', 'payment']);

        if ($this->isNonRefundableBooking($booking)) {
            return ['ok' => false, 'error' => 'Loại phòng này không cho phép hủy / không hoàn tiền (Non-refundable). Vui lòng liên hệ khách sạn.', 'code' => self::ERR_NON_REFUNDABLE];
        }

        $eval = $this->evaluatePolicy($booking);
        if ($eval['hours_until'] < 0) {
            return ['ok' => false, 'error' => 'Đã qua thời điểm nhận phòng, không thể hủy trực tuyến.', 'code' => self::ERR_PAST_CHECK_IN];
        }

        $hotel = HotelInfo::query()->first();
        $requireAdmin = (bool) ($hotel?->cancel_require_admin_when_penalty ?? true);
        $pendingPath = $requireAdmin
            && $eval['paid_amount'] > 0
            && $eval['penalty_percent'] > 0;

        if (! $booking->payment || $booking->payment->status !== 'paid') {
            DB::transaction(function () use ($booking, $reason, $userId) {
                $old = $booking->status;
                $booking->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $reason,
                    'cancellation_requested_at' => now(),
                ]);
                $booking->releaseBookedDates();
                $this->writeLog($booking, $old, 'cancelled', $userId, $reason, 'customer_cancel_unpaid');
                $this->notifyCustomer($booking, 'Đơn của bạn đã được hủy (chưa thanh toán).');
            });

            $booking->refresh();

            return ['ok' => true, 'booking' => $booking, 'mode' => 'cancelled_unpaid'];
        }

        if ($pendingPath) {
            DB::transaction(function () use ($booking, $eval, $reason, $userId) {
                $old = $booking->status;
                $payment = $booking->payment;
                if ($payment && $payment->status === 'paid') {
                    $payment->update([
                        'refund_penalty_amount' => $eval['penalty_amount'],
                        'refund_eligible_amount' => $eval['eligible_amount'],
                    ]);
                }
                $booking->update([
                    'status' => 'cancellation_pending',
                    'cancellation_reason' => $reason,
                    'cancellation_requested_at' => now(),
                ]);
                $this->writeLog($booking, $old, 'cancellation_pending', $userId, $reason, 'customer_cancel_pending');
                $this->notifyCustomer(
                    $booking,
                    'Yêu cầu hủy đã được ghi nhận. Khách sạn sẽ xác nhận; phí hủy dự kiến: '
                    . number_format($eval['penalty_amount'], 0, ',', '.') . ' ₫.'
                );
            });
            $booking->refresh();

            return ['ok' => true, 'booking' => $booking, 'mode' => 'pending_admin', 'evaluation' => $eval];
        }

        DB::transaction(function () use ($booking, $eval, $reason, $userId) {
            $old = $booking->status;
            $booking->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancellation_requested_at' => now(),
            ]);
            $booking->releaseBookedDates();

            $payment = $booking->payment;
            if ($payment && $payment->status === 'paid') {
                $payment->update([
                    'refund_penalty_amount' => $eval['penalty_amount'],
                    'refund_eligible_amount' => $eval['eligible_amount'],
                ]);
                $payment->beginRefundFlowIfPaid();
            }

            $this->writeLog($booking, $old, 'cancelled', $userId, $reason, 'customer_cancel');
            $msg = 'Đơn đã được hủy.';
            if ($eval['eligible_amount'] > 0) {
                $msg .= ' Số tiền hoàn dự kiến: '.number_format($eval['eligible_amount'], 0, ',', '.').' ₫ (đã trừ phí hủy).';
            } elseif ($booking->payment && $booking->payment->status === 'paid') {
                $msg .= ' Theo chính sách, không có khoản hoàn tiền.';
            }
            $this->notifyCustomer($booking, $msg);
        });

        $booking->refresh();

        return ['ok' => true, 'booking' => $booking, 'mode' => 'cancelled', 'evaluation' => $eval];
    }

    public function approveCancellationByAdmin(Booking $booking, int $adminUserId): void
    {
        if ($booking->status !== 'cancellation_pending') {
            abort(422, 'Đơn không ở trạng thái chờ hủy.');
        }

        $booking->load('payment');

        DB::transaction(function () use ($booking, $adminUserId) {
            $old = $booking->status;
            $booking->update([
                'status' => 'cancelled',
            ]);
            $booking->releaseBookedDates();

            $payment = $booking->payment;
            if ($payment && $payment->status === 'paid') {
                $payment->beginRefundFlowIfPaid();
            }

            $this->writeLog($booking, $old, 'cancelled', $adminUserId, null, 'admin_approve_cancel');
            $this->notifyCustomer($booking, 'Khách sạn đã chấp nhận hủy đơn #'.$booking->id.'.');
        });
    }

    public function rejectCancellationByAdmin(Booking $booking, int $adminUserId, ?string $note = null): void
    {
        if ($booking->status !== 'cancellation_pending') {
            abort(422, 'Đơn không ở trạng thái chờ hủy.');
        }

        $booking->load('payment');

        DB::transaction(function () use ($booking, $adminUserId, $note) {
            $old = $booking->status;
            $booking->update([
                'status' => 'confirmed',
                'cancellation_reason' => null,
                'cancellation_requested_at' => null,
            ]);

            $payment = $booking->payment;
            if ($payment) {
                $payment->update([
                    'refund_penalty_amount' => null,
                    'refund_eligible_amount' => null,
                ]);
            }

            $this->writeLog($booking, $old, 'confirmed', $adminUserId, $note, 'admin_reject_cancel');
            $this->notifyCustomer(
                $booking,
                'Yêu cầu hủy đơn #'.$booking->id.' không được chấp nhận. Đơn vẫn hiệu lực.'
            );
        });
    }

    protected function writeLog(
        Booking $booking,
        string $oldStatus,
        string $newStatus,
        ?int $actorUserId,
        ?string $note,
        string $action
    ): void {
        BookingLog::create([
            'booking_id' => $booking->id,
            'actor_user_id' => $actorUserId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => $note ? '['.$action.'] '.$note : '['.$action.']',
            'changed_at' => now(),
        ]);
    }

    protected function notifyCustomer(Booking $booking, string $line): void
    {
        $booking->loadMissing('user');
        if (! $booking->user?->email) {
            return;
        }
        try {
            Mail::to($booking->user->email)->send(new BookingCancellationMail($booking, $line));
        } catch (\Throwable $e) {
            Log::warning('booking.cancel.mail_failed', [
                'booking_id' => $booking->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
