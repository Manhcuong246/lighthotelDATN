<?php

namespace App\Support;

use App\Models\Booking;
use Illuminate\Http\Request;

/**
 * Một lần mở cổng xem /payment/success sau khi VNPay redirect về (cùng phiên trình duyệt),
 * tránh 403 khi signed URL bị lệch scheme/host so với lúc tạo (ngrok, APP_URL, proxy).
 */
final class VnPaySuccessSession
{
    public const SESSION_KEY = 'vnpay_success_gate';

    public static function grant(Booking $booking): void
    {
        session([
            self::SESSION_KEY => [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'expires_at' => now()->addMinutes(45)->timestamp,
                'token' => self::makeToken($booking),
            ],
        ]);
    }

    public static function makeToken(Booking $booking): string
    {
        return hash_hmac(
            'sha256',
            (string) $booking->id.'|'.(string) $booking->user_id,
            (string) config('app.key')
        );
    }

    /**
     * Đúng booking + còn hạn + token khớp → xóa session và trả true.
     */
    public static function consume(Request $request, Booking $booking): bool
    {
        $gate = session(self::SESSION_KEY);
        if (! is_array($gate)) {
            return false;
        }

        if ((int) ($gate['booking_id'] ?? 0) !== (int) $booking->id) {
            return false;
        }

        if ((int) ($gate['expires_at'] ?? 0) < now()->timestamp) {
            session()->forget(self::SESSION_KEY);

            return false;
        }

        $token = (string) ($gate['token'] ?? '');
        $expected = self::makeToken($booking);
        if ($token === '' || ! hash_equals($expected, $token)) {
            return false;
        }

        session()->forget(self::SESSION_KEY);

        return true;
    }

    public static function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
