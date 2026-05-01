<?php

namespace App\Services;

use App\Mail\PaymentInstructionFallbackHtml;
use App\Mail\PaymentInstructionMail;
use App\Models\Booking;
use App\Models\HotelInfo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class PaymentInstructionDelivery
{
    /**
     * Gửi email đặt phòng / thanh toán: thử template Blade, lỗi thì gửi HTML dự phòng (không Blade).
     * Không ném exception ra ngoài — mọi lỗi ghi log.
     */
    public static function send(
        Booking $booking,
        ?HotelInfo $hotelInfo,
        int $nights,
        ?string $qrCodeUrl,
        ?string $vnpayPayUrl,
        string $toEmail,
        bool $cashPaidAtDesk = false,
    ): void {
        $toEmail = trim($toEmail);
        if ($toEmail === '' || ! filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            Log::warning('payment_instruction.skip_invalid_email', ['booking_id' => $booking->id]);

            return;
        }

        $mailer = config('mail.default');
        if ($mailer === 'smtp') {
            $smtpUser = trim((string) config('mail.mailers.smtp.username'));
            $smtpPass = (string) config('mail.mailers.smtp.password');
            $fromAddr = trim((string) config('mail.from.address'));
            if ($smtpUser === '' || $smtpPass === '') {
                Log::warning('payment_instruction.mail_skip_missing_smtp_credentials', ['booking_id' => $booking->id]);

                return;
            }
            if ($fromAddr === '' || strcasecmp($fromAddr, 'hello@example.com') === 0) {
                Log::warning('payment_instruction.mail_skip_invalid_from', ['booking_id' => $booking->id]);

                return;
            }
        }

        $subject = PaymentInstructionFallbackHtml::subject($vnpayPayUrl, $cashPaidAtDesk, $booking->id);

        try {
            Mail::to($toEmail)->send(new PaymentInstructionMail(
                $booking,
                $hotelInfo,
                $nights,
                $qrCodeUrl,
                $vnpayPayUrl,
                $cashPaidAtDesk
            ));
            Log::info('payment_instruction.mail_sent', [
                'booking_id' => $booking->id,
                'mailer' => $mailer,
                'mode' => 'mailable',
            ]);

            return;
        } catch (\Throwable $e) {
            Log::warning('payment_instruction.mailable_failed_trying_fallback', [
                'booking_id' => $booking->id,
                'message' => $e->getMessage(),
            ]);
        }

        try {
            $html = PaymentInstructionFallbackHtml::build($booking, $hotelInfo, $nights, $qrCodeUrl, $vnpayPayUrl, $cashPaidAtDesk);
            Mail::html($html, function ($message) use ($toEmail, $subject) {
                $message->to($toEmail)->subject($subject);
            });
            Log::info('payment_instruction.mail_sent', [
                'booking_id' => $booking->id,
                'mailer' => $mailer,
                'mode' => 'fallback_html',
            ]);
        } catch (\Throwable $e) {
            Log::error('payment_instruction.mail_failed_completely', [
                'booking_id' => $booking->id,
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }
}
