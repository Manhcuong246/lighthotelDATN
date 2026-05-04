<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thanh toán VNPay thành công — đơn #{{ $booking->id }}</title>
</head>
@php
    $checkIn = \Carbon\Carbon::parse($booking->check_in);
    $checkOut = \Carbon\Carbon::parse($booking->check_out);
    $hotelName = $hotelInfo?->name ?? 'Light Hotel';
    $preheader = 'Đơn #'.$booking->id.' đã thanh toán — Mở biên lai / hóa đơn trực tiếp từ email.';
@endphp
<body style="margin:0;padding:0;background-color:#e8ecf2;-webkit-text-size-adjust:100%;">
<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
    {{ $preheader }}
</div>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#e8ecf2;">
    <tr>
        <td align="center" style="padding:32px 16px 48px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 12px 40px rgba(15,23,42,0.12);">
                <tr>
                    <td style="background:linear-gradient(135deg,#059669 0%,#047857 100%);padding:24px 28px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                                <td valign="middle" style="width:1%;padding-right:14px;">
                                    @if(!empty($logoUrl))
                                        <img src="{{ $logoUrl }}" alt="" width="200" height="40" style="display:block;border-radius:10px;background:#fff;padding:6px 10px;height:40px;width:auto;max-width:200px;object-fit:contain;">
                                    @else
                                        <div style="width:56px;height:40px;border-radius:10px;background:rgba(255,255,255,0.2);text-align:center;line-height:40px;font-size:22px;color:#fff;">✦</div>
                                    @endif
                                </td>
                                <td valign="middle" style="font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
                                    <p style="margin:0;font-size:11px;color:rgba(255,255,255,0.9);text-transform:uppercase;letter-spacing:0.14em;">VNPay · Đã thanh toán</p>
                                    <p style="margin:6px 0 0;font-size:20px;font-weight:700;color:#ffffff;">{{ $hotelName }}</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px 28px 8px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
                        <p style="margin:0 0 12px;font-size:22px;font-weight:700;color:#0f172a;line-height:1.25;">
                            Cảm ơn Quý khách — đơn <span style="color:#059669;">#{{ $booking->id }}</span> đã được xác nhận
                        </p>
                        <p style="margin:0 0 20px;font-size:15px;line-height:1.65;color:#475569;">
                            Xin chào <strong style="color:#0f172a;">{{ $booking->user->full_name ?? 'Quý khách' }}</strong>,
                            chúng tôi đã ghi nhận thanh toán VNPay. Dưới đây là tóm tắt đặt phòng. <strong>Số phòng cụ thể</strong> do lễ tân bố trí khi nhận phòng (trừ khi đơn đã ghi phòng vật lý trong hệ thống). Bấm <strong>Chi tiết đơn</strong> để xem đơn và yêu cầu hoàn tiền (nếu đủ điều kiện) — không cần đăng nhập. Có thể mở thêm biên lai/hóa đơn từ nút phụ bên dưới.
                        </p>
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8fafc;border-radius:14px;border:1px solid #e2e8f0;">
                            <tr>
                                <td style="padding:18px 20px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;color:#334155;line-height:1.6;">
                                    <strong style="color:#0f172a;">Nhận phòng:</strong> {{ $checkIn->format('d/m/Y') }}<br>
                                    <strong style="color:#0f172a;">Trả phòng:</strong> {{ $checkOut->format('d/m/Y') }}<br>
                                    <strong style="color:#0f172a;">Số đêm:</strong> {{ $nights }}<br>
                                    <strong style="color:#0f172a;">Tổng tiền:</strong> {{ number_format($booking->total_price, 0, ',', '.') }} ₫
                                </td>
                            </tr>
                        </table>
                        <div style="text-align:center;padding:28px 0 12px;">
                            <a href="{{ $bookingDetailUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:linear-gradient(180deg,#059669 0%,#047857 100%);color:#ffffff;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:16px;font-weight:700;text-decoration:none;padding:16px 36px;border-radius:12px;box-shadow:0 4px 14px rgba(5,150,105,0.35);">
                                Xem chi tiết đơn
                            </a>
                        </div>
                        @if(!empty($invoiceUrl) && $invoiceUrl !== $bookingDetailUrl)
                        <div style="text-align:center;padding:0 0 12px;">
                            <a href="{{ $invoiceUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;color:#0369a1;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:15px;font-weight:600;text-decoration:underline;">
                                Xem biên lai &amp; hóa đơn
                            </a>
                        </div>
                        @endif
                        <p style="margin:0 0 24px;font-size:13px;line-height:1.55;color:#64748b;text-align:center;">
                            Link có thời hạn (khoảng {{ (int) config('booking.signed_booking_show_ttl_days', 90) }} ngày). Không chia sẻ email này cho người khác.
                        </p>
                        @if(!empty($guestPortalIndexUrl))
                        <p style="margin:0 0 20px;font-size:13px;line-height:1.55;color:#64748b;text-align:center;">
                            <a href="{{ $guestPortalIndexUrl }}" target="_blank" rel="noopener noreferrer" style="color:#0369a1;font-weight:600;">Xem tất cả đơn của tài khoản này</a>
                        </p>
                        @endif
                        <p style="margin:0;font-size:13px;line-height:1.55;color:#94a3b8;text-align:center;">
                            Nếu đã có tài khoản, Quý khách có thể vào mục «Đơn của tôi» sau khi đăng nhập.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 28px 28px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;color:#94a3b8;line-height:1.5;">
                        @if(!empty($hotelInfo?->phone))
                            Hotline: {{ $hotelInfo->phone }}
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
