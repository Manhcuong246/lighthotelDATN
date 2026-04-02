<!DOCTYPE html>
<html>
<head>
    <title>Kết quả yêu cầu hoàn tiền</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
        <h2 style="color: {{ $refundRequest->status === 'refunded' ? '#5cb85c' : '#d9534f' }};">Kết quả xử lý yêu cầu hoàn tiền #{{ $refundRequest->booking_id }}</h2>
        <p>Chào <strong>{{ $refundRequest->user->full_name }}</strong>,</p>
        <p>Light Hotel xin gửi kết quả xử lý yêu cầu hủy phòng và hoàn tiền của bạn.</p>
        
        <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p style="margin: 5px 0;"><strong>Trạng thái:</strong> 
                <span style="color: {{ $refundRequest->status === 'refunded' ? '#5cb85c' : '#d9534f' }}; fw-bold;">
                    @if($refundRequest->status === 'refunded') Chấp nhận và hoàn tiền thành công
                    @else Đã bị từ chối
                    @endif
                </span>
            </p>
            <p style="margin: 5px 0;"><strong>Số tiền:</strong> {{ number_format($refundRequest->refund_amount, 0, ',', '.') }} ₫</p>
            <p style="margin: 5px 0;"><strong>Vào ngày:</strong> {{ now()->format('d/m/Y H:i') }}</p>
        </div>

        @if($refundRequest->admin_note)
        <p><strong>Ghi chú từ quản trị viên:</strong></p>
        <div style="padding: 10px; border-left: 4px solid #ddd; background: #eee; margin-bottom: 20px;">
            {{ $refundRequest->admin_note }}
        </div>
        @endif

        @if($refundRequest->status === 'refunded')
        <p>Đơn đặt phòng của bạn đã được hủy bỏ vĩnh viễn và số tiền đã được chuyển về tài khoản ngân hàng của bạn. Vui lòng kiểm tra tài khoản.</p>
        @else
        <p>Yêu cầu hoàn tiền bị từ chối, đơn đặt phòng của bạn vẫn giữ trạng thái đã xác nhận.</p>
        @endif

        <a href="{{ route('account.bookings.show', $refundRequest->booking_id) }}" style="display: inline-block; padding: 10px 20px; background: #0275d8; color: #fff; text-decoration: none; border-radius: 5px;">Xem lại đơn hàng</a>
        
        <p style="margin-top: 30px; font-size: 0.8em; color: #777;">Trân trọng,<br>Đội ngũ Light Hotel</p>
    </div>
</body>
</html>
