<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\HotelInfo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

/**
 * Email HTML đầy đủ (không Blade) — dùng khi Mailable/view lỗi.
 * Bố cục table + hero ảnh + logo + nút CTA, tương thích Gmail.
 */
final class PaymentInstructionFallbackHtml
{
    private const FF = "'Segoe UI',Roboto,Helvetica,Arial,sans-serif";

    public static function subject(?string $vnpayPayUrl, bool $cashPaidAtDesk, int $bookingId): string
    {
        if ($vnpayPayUrl) {
            return '[Light Hotel] Link thanh toán VNPay — đơn #'.$bookingId;
        }
        if ($cashPaidAtDesk) {
            return '[Light Hotel] Xác nhận đặt phòng — đơn #'.$bookingId;
        }

        return '[Light Hotel] Thông tin chuyển khoản — đơn #'.$bookingId;
    }

    public static function build(
        Booking $booking,
        ?HotelInfo $hotelInfo,
        int $nights,
        ?string $qrCodeUrl,
        ?string $vnpayPayUrl,
        bool $cashPaidAtDesk,
    ): string {
        $booking->loadMissing([
            'user',
            'room.roomType',
            'rooms.roomType',
            'bookingRooms.room.roomType',
            'bookingRooms.roomType',
        ]);

        $baseUrl = rtrim((string) config('app.url'), '/');
        $heroUrl = trim((string) (config('app.payment_mail_hero_url')
            ?: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&auto=format&fit=crop&q=80'));
        if ($heroUrl === '') {
            $heroUrl = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&auto=format&fit=crop&q=80';
        }

        $logoUrl = null;
        if (is_file(storage_path('app/public/logo.png'))) {
            $logoUrl = $baseUrl.'/storage/logo.png';
        }

        $hotelName = e((string) ($hotelInfo?->name ?? 'Light Hotel'));
        $guest = e((string) ($booking->user->full_name ?? 'Quý khách'));
        $id = (int) $booking->id;
        $totalFmt = number_format((float) $booking->total_price, 0, ',', '.');
        $checkIn = Carbon::parse($booking->check_in);
        $checkOut = Carbon::parse($booking->check_out);
        $ci = e($checkIn->format('d/m/Y'));
        $co = e($checkOut->format('d/m/Y'));
        $nights = max(1, $nights);

        $preheader = self::preheaderText($booking, $vnpayPayUrl, $cashPaidAtDesk);

        $addressLine = '';
        if ($hotelInfo && trim((string) ($hotelInfo->address ?? '')) !== '') {
            $addressLine = e(trim((string) $hotelInfo->address));
        } else {
            $addressLine = '✨ '.e('Đặt phòng · Trải nghiệm lưu trú').' ✨';
        }

        $eyebrow = $cashPaidAtDesk ? 'Xác nhận đơn' : 'Thanh toán đặt phòng';
        if ($vnpayPayUrl) {
            $headline = 'Hoàn tất thanh toán đơn <span style="color:#0369a1;">#'.e((string) $id).'</span>';
        } elseif ($cashPaidAtDesk) {
            $headline = 'Đơn đặt phòng <span style="color:#0369a1;">#'.e((string) $id).'</span> · Đã thanh toán tiền mặt';
        } else {
            $headline = 'Hướng dẫn chuyển khoản · Đơn <span style="color:#0369a1;">#'.e((string) $id).'</span>';
        }

        $introTail = '';
        if ($vnpayPayUrl) {
            $introTail = ' Dưới đây là thông tin đặt phòng và link thanh toán VNPay.';
        } elseif ($cashPaidAtDesk) {
            $introTail = ' Khách sạn đã ghi nhận thanh toán <strong style="color:#0f172a;">tiền mặt</strong> cho đơn này. Dưới đây là tóm tắt đặt phòng để Quý khách lưu.';
        } else {
            $introTail = ' Dưới đây là thông tin đặt phòng và cách thanh toán.';
        }

        $amountLabel = $cashPaidAtDesk ? 'Tổng đã thanh toán' : 'Số tiền cần thanh toán';

        $bookingRoomItems = self::bookingRoomItems($booking);
        $childrenCount = (int) $bookingRoomItems->sum('children_0_5') + (int) $bookingRoomItems->sum('children_6_11');
        $adultsSum = (int) ($bookingRoomItems->sum('adults') ?: ($booking->adults ?? 1));

        $roomSummary = self::roomSummaryCell($booking, $bookingRoomItems);
        $guestSummary = e((string) $adultsSum).' người lớn';
        if ($childrenCount > 0) {
            $guestSummary .= ' + '.e((string) $childrenCount).' trẻ em';
        }

        $detailRows = self::detailRowsHtml($bookingRoomItems);

        $vnpayTxnMinutes = (int) config('vnpay.transaction_expire_minutes', 15);
        $payLinkDays = (int) config('vnpay.pay_entry_signed_ttl_days', 14);

        $middleBlocks = '';
        if ($vnpayPayUrl !== null && $vnpayPayUrl !== '') {
            $payHref = self::escAttr($vnpayPayUrl);
            $middleBlocks .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eff6ff;border-radius:14px;border:1px solid #bfdbfe;margin-bottom:24px;">
<tr><td style="padding:16px 18px;font-family:'.self::FF.';font-size:13px;line-height:1.6;color:#1e40af;">
<strong>Thanh toán VNPay:</strong> phiên giao dịch (~<strong>'.$vnpayTxnMinutes.' phút</strong>) bắt đầu khi Quý khách bấm nút bên dưới. Link còn hiệu lực khoảng <strong>'.$payLinkDays.' ngày</strong>; mỗi lần bấm sẽ tạo phiên thanh toán mới.
</td></tr></table>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
<tr><td align="center" style="padding:8px 0 16px;">
<a href="'.$payHref.'" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:#059669;color:#ffffff;font-family:'.self::FF.';font-size:16px;font-weight:700;text-decoration:none;padding:16px 40px;border-radius:12px;box-shadow:0 4px 14px rgba(5,150,105,0.35);">Thanh toán ngay qua VNPay</a>
</td></tr>
<tr><td align="center" style="font-family:'.self::FF.';font-size:12px;color:#94a3b8;padding-bottom:16px;">Nút mở trang thanh toán được bảo vệ — vui lòng không chia sẻ link.</td></tr>
</table>';
        }

        if ($vnpayPayUrl === '' || $vnpayPayUrl === null) {
            if ($cashPaidAtDesk) {
                $middleBlocks .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ecfdf5;border-radius:14px;border:1px solid #a7f3d0;margin-bottom:24px;">
<tr><td style="padding:16px 18px;font-family:'.self::FF.';font-size:14px;line-height:1.65;color:#065f46;">
<strong>Đã thanh toán tiền mặt:</strong> khách sạn đã ghi nhận đủ số tiền cho đơn này. Không cần chuyển khoản hay thanh toán thêm qua email.
</td></tr></table>';
            } elseif ($hotelInfo) {
                $bankLabel = e((string) ($hotelInfo->bank_name ?? (($hotelInfo->bank_id ?? '') !== '' ? strtoupper((string) $hotelInfo->bank_id) : '—')));
                $middleBlocks .= '
<p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#475569;font-family:'.self::FF.';">Quý khách vui lòng chuyển khoản đúng <strong>nội dung</strong> bên dưới để đơn được xử lý tự động.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-radius:14px;overflow:hidden;border:1px solid #e2e8f0;margin-bottom:20px;">
<tr style="background:#f8fafc;"><td style="padding:14px 16px;font-size:13px;color:#64748b;width:40%;border-bottom:1px solid #e2e8f0;font-family:'.self::FF.';">Ngân hàng</td>
<td style="padding:14px 16px;font-size:14px;font-weight:600;color:#0f172a;border-bottom:1px solid #e2e8f0;font-family:'.self::FF.';">'.$bankLabel.'</td></tr>
<tr><td style="padding:14px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #e2e8f0;font-family:'.self::FF.';">Số tài khoản</td>
<td style="padding:14px 16px;font-size:17px;font-weight:700;color:#0f172a;letter-spacing:0.04em;border-bottom:1px solid #e2e8f0;font-family:'.self::FF.';">'.e((string) ($hotelInfo->bank_account ?? '—')).'</td></tr>
<tr style="background:#f8fafc;"><td style="padding:14px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #e2e8f0;font-family:'.self::FF.';">Chủ TK</td>
<td style="padding:14px 16px;font-size:14px;color:#0f172a;border-bottom:1px solid #e2e8f0;font-family:'.self::FF.';">'.e((string) ($hotelInfo->bank_account_name ?? '—')).'</td></tr>
<tr style="background:#fffbeb;"><td style="padding:14px 16px;font-size:13px;color:#92400e;font-family:'.self::FF.';">Nội dung CK</td>
<td style="padding:14px 16px;font-size:16px;font-weight:800;color:#b45309;font-family:'.self::FF.';">BOOKING_'.$id.'</td></tr>
</table>';
                if ($qrCodeUrl !== null && $qrCodeUrl !== '') {
                    $qr = self::escAttr($qrCodeUrl);
                    $middleBlocks .= '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
<tr><td align="center" style="padding:20px;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:16px;">
<p style="margin:0 0 12px;font-size:13px;color:#64748b;font-family:'.self::FF.';">Quét mã VietQR để chuyển khoản nhanh</p>
<img src="'.$qr.'" alt="QR chuyển khoản" width="220" style="display:block;margin:0 auto;max-width:220px;height:auto;border-radius:12px;border:4px solid #fff;box-shadow:0 4px 16px rgba(0,0,0,0.08);">
</td></tr></table>';
                }
            }
        }

        $accountUrl = self::safeAccountBookingsUrl();

        $histLine = $cashPaidAtDesk
            ? 'Quý khách có thể đăng nhập và xem <strong>Lịch sử đặt phòng</strong>:'
            : 'Sau khi thanh toán, Quý khách có thể đăng nhập và xem <strong>Lịch sử đặt phòng</strong>:';

        $contactBlock = '';
        if ($hotelInfo && trim((string) ($hotelInfo->phone ?? '')) !== '') {
            $contactBlock .= '<strong>Hotline:</strong> '.e(trim((string) $hotelInfo->phone)).'<br>';
        }
        if ($hotelInfo && trim((string) ($hotelInfo->email ?? '')) !== '') {
            $em = e(trim((string) $hotelInfo->email));
            $contactBlock .= '<strong>Email:</strong> <a href="mailto:'.$em.'" style="color:#0369a1;">'.$em.'</a><br>';
        }

        $logoCell = '';
        if ($logoUrl !== null) {
            $lg = self::escAttr($logoUrl);
            $logoCell = '<img src="'.$lg.'" alt="" width="200" height="40" style="display:block;border-radius:10px;background:#fff;padding:6px 10px;height:40px;width:auto;max-width:200px;object-fit:contain;">';
        } else {
            $logoCell = '<div style="width:56px;height:40px;border-radius:10px;background:rgba(255,255,255,0.15);text-align:center;line-height:40px;font-size:22px;color:#fff;">✦</div>';
        }

        $heroAttr = self::escAttr($heroUrl);

        return '<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>'.e(self::subject($vnpayPayUrl, $cashPaidAtDesk, $id)).'</title>
</head>
<body style="margin:0;padding:0;background-color:#e8ecf2;-webkit-text-size-adjust:100%;">
<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">'.e($preheader).'</div>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#e8ecf2;">
<tr><td align="center" style="padding:32px 16px 48px;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 12px 40px rgba(15,23,42,0.12);">
<tr><td style="padding:0;line-height:0;font-size:0;">
<img src="'.$heroAttr.'" width="600" alt="'.$hotelName.'" style="display:block;width:100%;max-width:600px;height:auto;border:0;outline:none;text-decoration:none;">
</td></tr>
<tr><td style="padding:0;line-height:0;font-size:0;height:5px;background:linear-gradient(90deg,#fbbf24 0%,#f59e0b 18%,#ec4899 40%,#8b5cf6 62%,#0ea5e9 82%,#059669 100%);"></td></tr>
<tr><td style="background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 50%,#0c4a6e 100%);padding:24px 28px 22px;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
<tr>
<td valign="middle" style="width:1%;padding-right:14px;">'.$logoCell.'</td>
<td valign="middle" style="font-family:'.self::FF.';">
<p style="margin:0;font-size:20px;font-weight:700;color:#ffffff;letter-spacing:-0.02em;">'.$hotelName.'</p>
<p style="margin:4px 0 0;font-size:13px;color:rgba(255,255,255,0.9);">'.$addressLine.'</p>
</td>
</tr>
</table>
</td></tr>
<tr><td style="padding:32px 28px 8px;font-family:'.self::FF.';">
<p style="margin:0 0 8px;font-size:14px;color:#64748b;text-transform:uppercase;letter-spacing:0.12em;">'.e($eyebrow).'</p>
<p style="margin:0 0 20px;font-size:22px;font-weight:700;color:#0f172a;letter-spacing:-0.03em;line-height:1.25;">'.$headline.'</p>
<p style="margin:0 0 24px;font-size:15px;line-height:1.65;color:#475569;">Kính gửi <strong style="color:#0f172a;">'.$guest.'</strong>, cảm ơn Quý khách đã lựa chọn '.$hotelName.'.'.$introTail.'</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 100%);border-radius:16px;border:1px solid #bae6fd;margin-bottom:24px;">
<tr><td style="padding:20px 22px;font-family:'.self::FF.';">
<p style="margin:0 0 4px;font-size:12px;color:#0369a1;text-transform:uppercase;letter-spacing:0.08em;">'.e($amountLabel).'</p>
<p style="margin:0;font-size:28px;font-weight:800;color:#0c4a6e;letter-spacing:-0.03em;">'.e($totalFmt).'&nbsp;₫</p>
<p style="margin:8px 0 0;font-size:13px;color:#475569;">'.$nights.' đêm · Nhận '.$ci.' → Trả '.$co.'</p>
</td></tr></table>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;margin-bottom:24px;">
<tr><td colspan="2" style="padding:14px 18px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-family:'.self::FF.';font-size:13px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:0.06em;">Chi tiết đặt phòng</td></tr>
<tr><td style="padding:12px 18px;font-size:14px;color:#64748b;width:42%;border-bottom:1px solid #f1f5f9;font-family:'.self::FF.';">Phòng</td>
<td style="padding:12px 18px;font-size:14px;font-weight:600;color:#0f172a;border-bottom:1px solid #f1f5f9;font-family:'.self::FF.';">'.$roomSummary.'</td></tr>
<tr><td style="padding:12px 18px;font-size:14px;color:#64748b;border-bottom:1px solid #f1f5f9;font-family:'.self::FF.';">Khách</td>
<td style="padding:12px 18px;font-size:14px;font-weight:600;color:#0f172a;border-bottom:1px solid #f1f5f9;font-family:'.self::FF.';">'.$guestSummary.'</td></tr>
'.$detailRows.'
</table>
'.$middleBlocks.'
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f1f5f9;border-radius:12px;margin-bottom:24px;">
<tr><td style="padding:16px 18px;font-family:'.self::FF.';font-size:13px;line-height:1.6;color:#475569;">
'.$histLine.'
<a href="'.self::escAttr($accountUrl).'" style="color:#0369a1;font-weight:600;">'.e($accountUrl).'</a>
</td></tr></table>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid #e2e8f0;padding-top:20px;">
<tr><td style="padding:8px 0;font-family:'.self::FF.';font-size:12px;color:#64748b;line-height:1.7;">
'.$contactBlock.'
Trân trọng,<br><span style="color:#0f172a;font-weight:600;">'.$hotelName.'</span>
</td></tr></table>
</td></tr>
</table>
<p style="margin:20px auto 0;max-width:600px;font-family:'.self::FF.';font-size:11px;color:#94a3b8;text-align:center;line-height:1.5;">
Email tự động từ hệ thống đặt phòng Light Hotel.<br>
© '.e((string) date('Y')).' '.$hotelName.'
</p>
</td></tr>
</table>
</body>
</html>';
    }

    private static function escAttr(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private static function preheaderText(Booking $booking, ?string $vnpayPayUrl, bool $cashPaidAtDesk): string
    {
        $amt = number_format((float) $booking->total_price, 0, ',', '.').' ₫';
        if ($vnpayPayUrl) {
            return 'Đơn #'.$booking->id.' — '.$amt.' — Thanh toán VNPay an toàn';
        }
        if ($cashPaidAtDesk) {
            return 'Đơn #'.$booking->id.' — Đã thanh toán tiền mặt — '.$amt;
        }

        return 'Đơn #'.$booking->id.' — '.$amt.' — Thông tin chuyển khoản';
    }

    private static function safeAccountBookingsUrl(): string
    {
        try {
            return URL::route('account.bookings', [], true);
        } catch (\Throwable) {
            return rtrim((string) config('app.url'), '/').'/account/bookings';
        }
    }

    private static function bookingRoomItems(Booking $booking): Collection
    {
        $bookingRoomItems = $booking->bookingRooms;
        if ($bookingRoomItems->isEmpty() && $booking->rooms->isNotEmpty()) {
            return $booking->rooms->map(function ($room) {
                return (object) [
                    'room' => $room,
                    'adults' => $room->pivot->adults ?? null,
                    'children_0_5' => $room->pivot->children_0_5 ?? 0,
                    'children_6_11' => $room->pivot->children_6_11 ?? 0,
                    'subtotal' => $room->pivot->subtotal ?? null,
                    'price_per_night' => $room->pivot->price_per_night ?? null,
                    'roomType' => null,
                ];
            });
        }

        return $bookingRoomItems;
    }

    private static function roomSummaryCell(Booking $booking, Collection $bookingRoomItems): string
    {
        if ($bookingRoomItems->isNotEmpty()) {
            return e((string) $bookingRoomItems->count()).' phòng đã chọn';
        }
        $room = $booking->room;
        if ($room) {
            try {
                return e((string) $room->displayLabel());
            } catch (\Throwable) {
                return e('Phòng #'.(string) ($room->id ?? ''));
            }
        }

        return e('Đang cập nhật');
    }

    private static function detailRowsHtml(Collection $bookingRoomItems): string
    {
        if ($bookingRoomItems->isEmpty()) {
            return '';
        }
        $html = '';
        foreach ($bookingRoomItems as $index => $item) {
            $detailRoom = $item->room ?? null;
            $detailLine = 'Phòng';
            try {
                if ($detailRoom) {
                    $detailLine = (string) $detailRoom->displayLabel();
                } else {
                    $rt = is_object($item) && isset($item->roomType) ? $item->roomType : null;
                    $name = $rt && isset($rt->name) ? (string) $rt->name : 'Phòng';
                    $detailLine = $name.' — số phòng do lễ tân bố trí';
                }
            } catch (\Throwable) {
                $detailLine = 'Phòng';
            }
            $detailLine = e($detailLine);

            $detailType = '';
            try {
                $dn = $detailRoom?->roomType?->name ?? (is_object($item) && isset($item->roomType) ? ($item->roomType->name ?? null) : null);
                if ($dn) {
                    $detailType = '<span style="font-weight:500;color:#64748b;"> · '.e((string) $dn).'</span>';
                }
            } catch (\Throwable) {
                $detailType = '';
            }

            $detailAdults = (int) ($item->adults ?? 0);
            $detailChildren = (int) ($item->children_0_5 ?? 0) + (int) ($item->children_6_11 ?? 0);
            $subPart = '';
            if (isset($item->subtotal) && $item->subtotal !== null) {
                $subPart = '<span style="color:#0f766e;font-weight:600;"> · '.e(number_format((float) $item->subtotal, 0, ',', '.')).' ₫</span>';
            }

            $childrenPart = '';
            if ($detailChildren > 0) {
                $childrenPart = ' · '.e((string) $detailChildren).' trẻ';
            }

            $bg = ($index % 2 === 0) ? '#ffffff' : '#fafbfc';
            $html .= '
<tr style="background:'.$bg.';">
<td colspan="2" style="padding:14px 18px;font-family:'.self::FF.';border-bottom:1px solid #f1f5f9;">
<p style="margin:0 0 4px;font-size:14px;font-weight:600;color:#0f172a;">'.$detailLine.$detailType.'</p>
<p style="margin:0;font-size:13px;color:#64748b;">'.e((string) max(1, $detailAdults)).' người lớn'.$childrenPart.$subPart.'</p>
</td></tr>';
        }

        return $html;
    }
}
