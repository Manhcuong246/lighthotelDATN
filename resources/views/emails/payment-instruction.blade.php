<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Thanh toán đơn #{{ $booking->id }}</title>
    {{-- 
        VNPay Payment Instruction Email Template
        - Displays detailed booking information
        - Shows VNPay payment link with timeout info
        - Includes room, dates, guests, and pricing
    --}}
</head>
<body style="margin:0; padding:0; background-color:#eef1f6; -webkit-text-size-adjust:100%;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#eef1f6;">
        <tr>
            <td align="center" style="padding:24px 12px 40px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:560px; background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(26,43,74,0.08);">
                    {{-- Header --}}
                    <tr>
                        <td bgcolor="#1a2b4a" style="background:linear-gradient(135deg,#1a2b4a 0%,#2d4a7c 100%); background-color:#1a2b4a; padding:28px 28px 24px; text-align:center;">
                            <p style="margin:0 0 6px; font-family:Georgia,'Times New Roman',serif; font-size:22px; font-weight:600; color:#ffffff; letter-spacing:0.5px;">Light Hotel</p>
                            <p style="margin:0; font-family:Arial,Helvetica,sans-serif; font-size:13px; color:rgba(255,255,255,0.85);">Đà Nẵng</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 28px 8px; font-family:Arial,Helvetica,sans-serif;">
                            <p style="margin:0 0 16px; font-size:15px; line-height:1.6; color:#334155;">
                                Xin chào <strong style="color:#1a2b4a;">{{ $booking->user->full_name ?? 'Quý khách' }}</strong>,
                            </p>

                            @if(!empty($vnpayPayUrl))
                                <p style="margin:0 0 20px; font-size:15px; line-height:1.6; color:#475569;">
                                    Đơn <strong style="color:#1a2b4a;">#{{ $booking->id }}</strong> đang chờ thanh toán qua <strong>VNPay</strong>.
                                </p>

                                {{-- Chi tiết đặt phòng --}}
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8fafc; border-radius:12px; border:2px solid #e2e8f0; margin-bottom:20px;">
                                    <tr>
                                        <td style="padding:20px; font-family:Arial,Helvetica,sans-serif;">
                                            <p style="margin:0 0 16px; font-size:16px; font-weight:700; color:#1a2b4a; border-bottom:2px solid #3b82f6; padding-bottom:8px;">
                                                📋 Chi tiết đặt phòng
                                            </p>
                                            
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
                                            @endphp
                                            
                                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                                <tr>
                                                    <td style="padding:8px 0; font-size:14px; color:#64748b; width:40%;">🏨 Phòng:</td>
                                                    <td style="padding:8px 0; font-size:14px; font-weight:600; color:#1e293b;">
                                                        @if($bookingRoomItems->isNotEmpty())
                                                            {{ $bookingRoomItems->count() }} phòng
                                                        @elseif($booking->room)
                                                            Phòng {{ $booking->room->name ?? $booking->room->room_number ?? 'N/A' }}
                                                        @else
                                                            Đang cập nhật
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr style="background:#f1f5f9;">
                                                    <td style="padding:10px 8px; font-size:14px; color:#64748b;">📅 Nhận phòng:</td>
                                                    <td style="padding:10px 8px; font-size:14px; font-weight:600; color:#1e293b;">
                                                        {{ $checkIn->format('H:i') }} - {{ $checkIn->format('d/m/Y') }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:10px 8px; font-size:14px; color:#64748b;">📅 Trả phòng:</td>
                                                    <td style="padding:10px 8px; font-size:14px; font-weight:600; color:#1e293b;">
                                                        {{ $checkOut->format('H:i') }} - {{ $checkOut->format('d/m/Y') }}
                                                    </td>
                                                </tr>
                                                <tr style="background:#f1f5f9;">
                                                    <td style="padding:10px 8px; font-size:14px; color:#64748b;">🌙 Số đêm:</td>
                                                    <td style="padding:10px 8px; font-size:14px; font-weight:600; color:#1e293b;">
                                                        {{ $nights }} đêm
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:10px 8px; font-size:14px; color:#64748b;">👥 Số khách:</td>
                                                    <td style="padding:10px 8px; font-size:14px; font-weight:600; color:#1e293b;">
                                                        {{ $bookingRoomItems->sum('adults') ?: ($booking->adults ?? 1) }} người lớn
                                                        @php
                                                            $childrenCount = (int) $bookingRoomItems->sum('children_0_5') + (int) $bookingRoomItems->sum('children_6_11');
                                                        @endphp
                                                        @if($childrenCount > 0)
                                                            + {{ $childrenCount }} trẻ em
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>

                                            @if($bookingRoomItems->isNotEmpty())
                                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:14px; border:1px solid #dbeafe; border-radius:10px; overflow:hidden;">
                                                    <tr style="background:#eff6ff;">
                                                        <td style="padding:9px 10px; font-size:12px; color:#1e3a8a; font-weight:700;">Chi tiết từng phòng</td>
                                                    </tr>
                                                    @foreach($bookingRoomItems as $index => $item)
                                                        @php
                                                            $detailRoom = $item->room ?? null;
                                                            $detailRoomName = $detailRoom?->name ?? ('Phòng #'.($detailRoom?->id ?? 'N/A'));
                                                            $detailRoomType = $detailRoom?->roomType?->name;
                                                            $detailAdults = (int) ($item->adults ?? 0);
                                                            $detailChildren = (int) ($item->children_0_5 ?? 0) + (int) ($item->children_6_11 ?? 0);
                                                        @endphp
                                                        <tr style="{{ $index % 2 === 0 ? 'background:#ffffff;' : 'background:#f8fafc;' }}">
                                                            <td style="padding:10px; font-size:13px; color:#1e293b;">
                                                                <strong>{{ $detailRoomName }}</strong>
                                                                @if($detailRoomType) · {{ $detailRoomType }} @endif
                                                                <br>
                                                                <span style="color:#64748b;">{{ max(1, $detailAdults) }} người lớn{{ $detailChildren > 0 ? ' + '.$detailChildren.' trẻ em' : '' }}</span>
                                                                @if(!is_null($item->subtotal))
                                                                    <br>
                                                                    <span style="color:#0f766e; font-weight:600;">Tạm tính: {{ number_format((float) $item->subtotal, 0, ',', '.') }} đ</span>
                                                                @elseif(!is_null($item->price_per_night))
                                                                    <br>
                                                                    <span style="color:#0f766e; font-weight:600;">Giá/đêm: {{ number_format((float) $item->price_per_night, 0, ',', '.') }} đ</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </table>
                                            @endif
                                        </td>
                                    </tr>
                                </table>

                                {{-- Gợi ý thời hạn — không hiển thị URL --}}
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f0f7ff; border-radius:12px; border:1px solid #bfdbfe;">
                                    <tr>
                                        <td style="padding:16px 18px; font-family:Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#1e40af;">
                                            Phiên thanh toán (~<strong>{{ $vnpayTxnMinutes }} phút</strong>) tính từ khi bạn bấm nút bên dưới — không phải từ lúc email được gửi. Link mở trang thanh toán có hiệu lực khoảng <strong>{{ $payLinkDays }} ngày</strong>; mỗi lần bấm sẽ tạo phiên VNPay mới.
                                        </td>
                                    </tr>
                                </table>

                                {{-- Tổng tiền mini --}}
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:20px;">
                                    <tr>
                                        <td style="padding:14px 16px; background:#fafbfc; border-radius:10px; border:1px solid #e2e8f0; font-family:Arial,Helvetica,sans-serif;">
                                            <span style="font-size:13px; color:#64748b;">Số tiền thanh toán</span><br>
                                            <span style="font-size:22px; font-weight:700; color:#1a2b4a; letter-spacing:-0.5px;">{{ number_format($booking->total_price, 0, ',', '.') }}&nbsp;₫</span>
                                        </td>
                                    </tr>
                                </table>

                                {{-- CTA: nút chính + “ảnh” banner (table cell giả ảnh, link bọc cả khối) --}}
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:26px;">
                                    <tr>
                                        <td align="center" style="padding:0 0 8px;">
                                            <a href="{{ $vnpayPayUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block; text-decoration:none;">
                                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-radius:14px; overflow:hidden; box-shadow:0 6px 20px rgba(220,38,38,0.35);">
                                                    <tr>
                                                        <td align="center" bgcolor="#dc2626" style="background:linear-gradient(180deg,#ef4444 0%,#dc2626 55%,#b91c1c 100%); background-color:#dc2626; padding:18px 40px; font-family:Arial,Helvetica,sans-serif;">
                                                            <span style="display:block; font-size:12px; color:rgba(255,255,255,0.92); text-transform:uppercase; letter-spacing:1.2px; margin-bottom:6px;">Thanh toán an toàn</span>
                                                            <span style="display:block; font-size:18px; font-weight:700; color:#ffffff;">💳 Thanh toán qua VNPay</span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="center" style="padding:8px 0 0; font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#94a3b8;">
                                            Bấm vào khối đỏ phía trên để mở trang thanh toán.
                                        </td>
                                    </tr>
                                </table>

                            @else
                                <p style="margin:0 0 18px; font-size:15px; line-height:1.6; color:#475569;">
                                    Vui lòng chuyển khoản theo thông tin sau cho đơn <strong style="color:#1a2b4a;">#{{ $booking->id }}</strong>:
                                </p>

                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #e2e8f0; border-radius:12px; overflow:hidden;">
                                    <tr style="background:#f8fafc;">
                                        <td style="padding:12px 16px; font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#64748b; width:42%; border-bottom:1px solid #e2e8f0;">Ngân hàng</td>
                                        <td style="padding:12px 16px; font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#1e293b; border-bottom:1px solid #e2e8f0;">{{ $hotelInfo->bank_name ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 16px; font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#64748b; border-bottom:1px solid #e2e8f0;">Số tài khoản</td>
                                        <td style="padding:12px 16px; font-family:Arial,Helvetica,sans-serif; font-size:15px; font-weight:600; color:#1a2b4a; letter-spacing:0.5px; border-bottom:1px solid #e2e8f0;">{{ $hotelInfo->bank_account ?? '—' }}</td>
                                    </tr>
                                    <tr style="background:#f8fafc;">
                                        <td style="padding:12px 16px; font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#64748b; border-bottom:1px solid #e2e8f0;">Chủ tài khoản</td>
                                        <td style="padding:12px 16px; font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#1e293b; border-bottom:1px solid #e2e8f0;">{{ $hotelInfo->bank_account_name ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 16px; font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#64748b; border-bottom:1px solid #e2e8f0;">Số tiền</td>
                                        <td style="padding:12px 16px; font-family:Arial,Helvetica,sans-serif; font-size:17px; font-weight:700; color:#059669; border-bottom:1px solid #e2e8f0;">{{ number_format($booking->total_price, 0, ',', '.') }} đ</td>
                                    </tr>
                                    <tr style="background:#fffbeb;">
                                        <td style="padding:12px 16px; font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#92400e;">Nội dung CK</td>
                                        <td style="padding:12px 16px; font-family:Arial,Helvetica,sans-serif; font-size:15px; font-weight:700; color:#b45309;">BOOKING_{{ $booking->id }}</td>
                                    </tr>
                                </table>

                                @if(!empty($qrCodeUrl))
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:22px;">
                                        <tr>
                                            <td align="center" style="padding:20px; background:#ffffff; border:1px dashed #cbd5e1; border-radius:12px;">
                                                <p style="margin:0 0 12px; font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#64748b;">Quét mã để chuyển khoản nhanh</p>
                                                <img src="{{ $qrCodeUrl }}" alt="Mã QR chuyển khoản" width="240" height="auto" style="display:block; margin:0 auto; max-width:240px; height:auto; border-radius:8px;">
                                            </td>
                                        </tr>
                                    </table>
                                @endif
                            @endif

                            @if(!empty($signedBookingViewUrl))
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:22px;">
                                    <tr>
                                        <td align="center" style="padding:0;">
                                            <a href="{{ $signedBookingViewUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block; background:#1a2b4a; color:#ffffff; font-family:Arial,Helvetica,sans-serif; font-size:14px; font-weight:600; text-decoration:none; padding:12px 24px; border-radius:10px;">Xem chi tiết đơn đặt phòng</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="center" style="padding:10px 8px 0; font-family:Arial,Helvetica,sans-serif; font-size:11px; color:#94a3b8; line-height:1.4;">
                                            Không cần đăng nhập — link có hiệu lực giới hạn thời gian.
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;">
                                <tr>
                                    <td style="padding:12px 14px; font-family:Arial,Helvetica,sans-serif; font-size:12px; line-height:1.6; color:#475569;">
                                        Sau khi thanh toán thành công, bạn có thể đăng nhập để xem <strong>Lịch sử đặt phòng</strong> tại:
                                        <a href="{{ route('account.bookings') }}" style="color:#1d4ed8; text-decoration:underline;">{{ route('account.bookings') }}</a>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:24px; padding-top:20px; border-top:1px solid #e2e8f0;">
                                <tr>
                                    <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#64748b; line-height:1.6;">
                                        <strong style="color:#334155;">Tóm tắt:</strong>
                                        Tổng thanh toán <strong style="color:#1a2b4a;">{{ number_format($booking->total_price, 0, ',', '.') }} đ</strong>
                                        · {{ $nights }} đêm
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:20px 0 0; font-family:Arial,Helvetica,sans-serif; font-size:13px; line-height:1.6; color:#94a3b8;">
                                Trân trọng,<br>
                                <span style="color:#64748b;">Light Hotel Đà Nẵng</span>
                            </p>
                        </td>
                    </tr>
                </table>
                <p style="margin:16px 0 0; font-family:Arial,Helvetica,sans-serif; font-size:11px; color:#94a3b8; text-align:center; max-width:560px;">
                    Email tự động — vui lòng không trả lời trực tiếp.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
