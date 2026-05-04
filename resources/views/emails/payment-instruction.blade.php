<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@if(!empty($vnpayPayUrl))Thanh toán VNPay@elseif($cashPaidAtDesk ?? false)Xác nhận đặt phòng@elseChuyển khoản thanh toán@endif — đơn #{{ $booking->id }}</title>
</head>
@php
    $checkIn = \Carbon\Carbon::parse($booking->check_in);
    $checkOut = \Carbon\Carbon::parse($booking->check_out);
    $bookingRoomItems = $booking->bookingRooms;
    if ($bookingRoomItems->isEmpty() && $booking->rooms->isNotEmpty()) {
        $bookingRoomItems = $booking->rooms->map(function ($room) {
            return (object) [
                'room' => $room,
                'adults' => $room->pivot->adults ?? null,
                'children_0_5' => $room->pivot->children_0_5 ?? 0,
                'children_6_11' => $room->pivot->children_6_11 ?? 0,
                'subtotal' => $room->pivot->subtotal ?? null,
                'price_per_night' => $room->pivot->price_per_night ?? null,
            ];
        });
    }
    $childrenCount = (int) $bookingRoomItems->sum('children_0_5') + (int) $bookingRoomItems->sum('children_6_11');
    $adultsSum = (int) ($bookingRoomItems->sum('adults') ?: ($booking->adults ?? 1));
    $hotelName = $hotelInfo?->name ?? 'Light Hotel';
    $heroUrl = !empty($heroImageUrl) ? $heroImageUrl : 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&auto=format&fit=crop&q=80';
    $bankLabel = $hotelInfo?->bank_name ?? (($hotelInfo?->bank_id) ? strtoupper((string) $hotelInfo->bank_id) : '—');
    $preheader = !empty($vnpayPayUrl)
        ? 'Đơn #'.$booking->id.' — '.number_format($booking->total_price, 0, ',', '.').' ₫ — Thanh toán VNPay an toàn'
        : (($cashPaidAtDesk ?? false)
            ? 'Đơn #'.$booking->id.' — Đã thanh toán tiền mặt tại khách sạn — '.number_format($booking->total_price, 0, ',', '.').' ₫'
            : 'Đơn #'.$booking->id.' — '.number_format($booking->total_price, 0, ',', '.').' ₫ — Thông tin chuyển khoản');
@endphp
<body style="margin:0;padding:0;background-color:#e8ecf2;-webkit-text-size-adjust:100%;">
{{-- Preheader (nhiều client hiển thị dòng xem trước) --}}
<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
    {{ $preheader }}
</div>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#e8ecf2;">
    <tr>
        <td align="center" style="padding:32px 16px 48px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 12px 40px rgba(15,23,42,0.12);">

                {{-- Hero --}}
                <tr>
                    <td style="padding:0;line-height:0;font-size:0;">
                        <img src="{{ $heroUrl }}" width="600" alt="{{ $hotelName }}" style="display:block;width:100%;max-width:600px;height:auto;border:0;outline:none;text-decoration:none;">
                    </td>
                </tr>
                <tr>
                    <td style="background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 50%,#0c4a6e 100%);padding:24px 28px 22px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                                <td valign="middle" style="width:1%;padding-right:14px;">
                                    @if(!empty($logoUrl))
                                        <img src="{{ $logoUrl }}" alt="" width="200" height="40" style="display:block;border-radius:10px;background:#fff;padding:6px 10px;height:40px;width:auto;max-width:200px;object-fit:contain;">
                                    @else
                                        <div style="width:56px;height:40px;border-radius:10px;background:rgba(255,255,255,0.15);text-align:center;line-height:40px;font-size:22px;">✦</div>
                                    @endif
                                </td>
                                <td valign="middle" style="font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
                                    <p style="margin:0;font-size:20px;font-weight:700;color:#ffffff;letter-spacing:-0.02em;">{{ $hotelName }}</p>
                                    <p style="margin:4px 0 0;font-size:13px;color:rgba(255,255,255,0.82);">
                                        @if(!empty($hotelInfo?->address))
                                            {{ $hotelInfo->address }}
                                        @else
                                            Đặt phòng · Trải nghiệm lưu trú
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:32px 28px 8px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
                        <p style="margin:0 0 8px;font-size:14px;color:#64748b;text-transform:uppercase;letter-spacing:0.12em;">
                            @if($cashPaidAtDesk ?? false)
                                Xác nhận đơn
                            @else
                                Thanh toán đặt phòng
                            @endif
                        </p>
                        <p style="margin:0 0 20px;font-size:22px;font-weight:700;color:#0f172a;letter-spacing:-0.03em;line-height:1.25;">
                            @if(!empty($vnpayPayUrl))
                                Hoàn tất thanh toán đơn <span style="color:#0369a1;">#{{ $booking->id }}</span>
                            @elseif($cashPaidAtDesk ?? false)
                                Đơn đặt phòng <span style="color:#0369a1;">#{{ $booking->id }}</span> · Đã thanh toán tiền mặt
                            @else
                                Hướng dẫn chuyển khoản · Đơn <span style="color:#0369a1;">#{{ $booking->id }}</span>
                            @endif
                        </p>
                        <p style="margin:0 0 24px;font-size:15px;line-height:1.65;color:#475569;">
                            Kính gửi <strong style="color:#0f172a;">{{ $booking->user->full_name ?? 'Quý khách' }}</strong>, cảm ơn Quý khách đã lựa chọn {{ $hotelName }}.
                            @if(!empty($vnpayPayUrl))
                                Dưới đây là thông tin đặt phòng và link thanh toán VNPay.
                            @elseif($cashPaidAtDesk ?? false)
                                Khách sạn đã ghi nhận thanh toán <strong>tiền mặt</strong> cho đơn này. Dưới đây là tóm tắt đặt phòng để Quý khách lưu.
                            @else
                                Dưới đây là thông tin đặt phòng và cách thanh toán.
                            @endif
                        </p>

                        {{-- Số tiền nổi bật --}}
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 100%);border-radius:16px;border:1px solid #bae6fd;margin-bottom:24px;">
                            <tr>
                                <td style="padding:20px 22px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
                                    <p style="margin:0 0 4px;font-size:12px;color:#0369a1;text-transform:uppercase;letter-spacing:0.08em;">{{ ($cashPaidAtDesk ?? false) ? 'Tổng đã thanh toán' : 'Số tiền cần thanh toán' }}</p>
                                    <p style="margin:0;font-size:28px;font-weight:800;color:#0c4a6e;letter-spacing:-0.03em;">{{ number_format($booking->total_price, 0, ',', '.') }}&nbsp;₫</p>
                                    <p style="margin:8px 0 0;font-size:13px;color:#475569;">{{ $nights }} đêm · Nhận {{ $checkIn->format('d/m/Y') }} → Trả {{ $checkOut->format('d/m/Y') }}</p>
                                </td>
                            </tr>
                        </table>

                        {{-- Chi tiết đặt --}}
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;margin-bottom:24px;">
                            <tr>
                                <td colspan="2" style="padding:14px 18px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:0.06em;">Chi tiết đặt phòng</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 18px;font-size:14px;color:#64748b;width:42%;border-bottom:1px solid #f1f5f9;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">Phòng</td>
                                <td style="padding:12px 18px;font-size:14px;font-weight:600;color:#0f172a;border-bottom:1px solid #f1f5f9;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
                                    @if($bookingRoomItems->isNotEmpty())
                                        {{ $bookingRoomItems->count() }} phòng đã chọn
                                    @elseif($booking->room)
                                        {{ $booking->room->displayLabel() }}
                                    @else
                                        Đang cập nhật
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:12px 18px;font-size:14px;color:#64748b;border-bottom:1px solid #f1f5f9;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">Khách</td>
                                <td style="padding:12px 18px;font-size:14px;font-weight:600;color:#0f172a;border-bottom:1px solid #f1f5f9;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
                                    {{ $adultsSum }} người lớn @if($childrenCount > 0)+ {{ $childrenCount }} trẻ em @endif
                                </td>
                            </tr>
                            @if($bookingRoomItems->isNotEmpty())
                                @foreach($bookingRoomItems as $index => $item)
                                    @php
                                        $detailRoom = $item->room ?? null;
                                        $detailLine = $detailRoom
                                            ? $detailRoom->displayLabel()
                                            : (($item->roomType?->name ?? 'Phòng').' — số phòng do lễ tân bố trí');
                                        $detailType = $detailRoom?->roomType?->name ?? $item->roomType?->name;
                                        $detailAdults = (int) ($item->adults ?? 0);
                                        $detailChildren = (int) ($item->children_0_5 ?? 0) + (int) ($item->children_6_11 ?? 0);
                                    @endphp
                                    <tr style="background:{{ $index % 2 === 0 ? '#ffffff' : '#fafbfc' }};">
                                        <td colspan="2" style="padding:14px 18px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;border-bottom:1px solid #f1f5f9;">
                                            <p style="margin:0 0 4px;font-size:14px;font-weight:600;color:#0f172a;">
                                            {{ $detailLine }}
                                            @if($detailType)
                                                <span style="font-weight:500;color:#64748b;"> · {{ $detailType }}</span>
                                            @endif
                                        </p>
                                        <p style="margin:0;font-size:13px;color:#64748b;">
                                            {{ max(1, $detailAdults) }} người lớn
                                            @if($detailChildren > 0)
                                                · {{ $detailChildren }} trẻ
                                            @endif
                                            @if(!is_null($item->subtotal))
                                                <span style="color:#0f766e;font-weight:600;"> · {{ number_format((float) $item->subtotal, 0, ',', '.') }} ₫</span>
                                            @endif
                                        </p>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </table>

                        @if(!empty($vnpayPayUrl))
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eff6ff;border-radius:14px;border:1px solid #bfdbfe;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:16px 18px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;line-height:1.6;color:#1e40af;">
                                        <strong>Thanh toán VNPay:</strong> phiên giao dịch (~<strong>{{ $vnpayTxnMinutes }} phút</strong>) bắt đầu khi Quý khách bấm nút bên dưới. Link mở trang thanh toán còn hiệu lực khoảng <strong>{{ $payLinkDays }} ngày</strong>; mỗi lần bấm sẽ tạo phiên thanh toán mới.
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:20px;">
                                <tr>
                                    <td align="center" style="padding:8px 0 20px;">
                                        <a href="{{ $vnpayPayUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:linear-gradient(180deg,#059669 0%,#047857 100%);color:#ffffff;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:16px;font-weight:700;text-decoration:none;padding:16px 36px;border-radius:12px;box-shadow:0 4px 14px rgba(5,150,105,0.35);">
                                            Thanh toán ngay qua VNPay
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;color:#94a3b8;padding-bottom:8px;">Nút mở trang thanh toán được bảo vệ — vui lòng không chia sẻ link.</td>
                                </tr>
                            </table>
                        @endif

                        @if(empty($vnpayPayUrl) && ($cashPaidAtDesk ?? false))
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ecfdf5;border-radius:14px;border:1px solid #a7f3d0;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:16px 18px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:1.65;color:#065f46;">
                                        <strong>Đã thanh toán tiền mặt:</strong> khách sạn đã ghi nhận đủ số tiền cho đơn này. Không cần chuyển khoản hay thanh toán thêm qua email.
                                    </td>
                                </tr>
                            </table>
                        @endif

                        @if(empty($vnpayPayUrl) && !($cashPaidAtDesk ?? false))
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#475569;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">Quý khách vui lòng chuyển khoản đúng <strong>nội dung</strong> bên dưới để đơn được xử lý tự động.</p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-radius:14px;overflow:hidden;border:1px solid #e2e8f0;margin-bottom:20px;">
                                <tr style="background:#f8fafc;">
                                    <td style="padding:14px 16px;font-size:13px;color:#64748b;width:40%;border-bottom:1px solid #e2e8f0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">Ngân hàng</td>
                                    <td style="padding:14px 16px;font-size:14px;font-weight:600;color:#0f172a;border-bottom:1px solid #e2e8f0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">{{ $bankLabel }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #e2e8f0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">Số tài khoản</td>
                                    <td style="padding:14px 16px;font-size:17px;font-weight:700;color:#0f172a;letter-spacing:0.04em;border-bottom:1px solid #e2e8f0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">{{ $hotelInfo?->bank_account ?? '—' }}</td>
                                </tr>
                                <tr style="background:#f8fafc;">
                                    <td style="padding:14px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #e2e8f0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">Chủ TK</td>
                                    <td style="padding:14px 16px;font-size:14px;color:#0f172a;border-bottom:1px solid #e2e8f0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">{{ $hotelInfo?->bank_account_name ?? '—' }}</td>
                                </tr>
                                <tr style="background:#fffbeb;">
                                    <td style="padding:14px 16px;font-size:13px;color:#92400e;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">Nội dung CK</td>
                                    <td style="padding:14px 16px;font-size:16px;font-weight:800;color:#b45309;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">BOOKING_{{ $booking->id }}</td>
                                </tr>
                            </table>

                            @if(!empty($qrCodeUrl))
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
                                    <tr>
                                        <td align="center" style="padding:20px;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:16px;">
                                            <p style="margin:0 0 12px;font-size:13px;color:#64748b;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">Quét mã VietQR để chuyển khoản nhanh</p>
                                            <img src="{{ $qrCodeUrl }}" alt="QR chuyển khoản" width="220" style="display:block;margin:0 auto;max-width:220px;height:auto;border-radius:12px;border:4px solid #fff;box-shadow:0 4px 16px rgba(0,0,0,0.08);">
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        @endif

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f1f5f9;border-radius:12px;margin-bottom:24px;">
                            <tr>
                                <td style="padding:16px 18px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;line-height:1.6;color:#475569;">
                                    @if($cashPaidAtDesk ?? false)
                                        Quý khách có thể đăng nhập và xem <strong>Lịch sử đặt phòng</strong>:
                                    @else
                                        Sau khi thanh toán, Quý khách có thể đăng nhập và xem <strong>Lịch sử đặt phòng</strong>:
                                    @endif
                                    <a href="{{ route('account.bookings') }}" style="color:#0369a1;font-weight:600;">{{ route('account.bookings') }}</a>
                                </td>
                            </tr>
                        </table>

                        {{-- Liên hệ --}}
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid #e2e8f0;padding-top:20px;">
                            <tr>
                                <td style="padding:8px 0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;color:#64748b;line-height:1.7;">
                                    @if(!empty($hotelInfo?->phone))<strong>Hotline:</strong> {{ $hotelInfo->phone }}<br>@endif
                                    @if(!empty($hotelInfo?->email))<strong>Email:</strong> <a href="mailto:{{ $hotelInfo->email }}" style="color:#0369a1;">{{ $hotelInfo->email }}</a><br>@endif
                                    Trân trọng,<br>
                                    <span style="color:#0f172a;font-weight:600;">{{ $hotelName }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <p style="margin:20px auto 0;max-width:600px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:11px;color:#94a3b8;text-align:center;line-height:1.5;">
                Email tự động từ hệ thống đặt phòng. Nếu Quý khách không thực hiện giao dịch, vui lòng bỏ qua hoặc liên hệ khách sạn.<br>
                © {{ date('Y') }} {{ $hotelName }}
            </p>
        </td>
    </tr>
</table>
</body>
</html>
