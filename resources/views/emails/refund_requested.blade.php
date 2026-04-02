<!DOCTYPE html>
<html>
<head>
    <title>Yêu cầu hoàn tiền mới</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
        <h2 style="color: #d9534f;">Yêu cầu hoàn tiền mới #{{ $refundRequest->booking_id }}</h2>
        <p>Chào Ban quản trị,</p>
        <p>Có một yêu cầu hoàn tiền mới từ khách hàng <strong>{{ $refundRequest->user->full_name }}</strong>.</p>
        
        <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p style="margin: 5px 0;"><strong>Đơn đặt phòng:</strong> #{{ $refundRequest->booking_id }}</p>
            <p style="margin: 5px 0;"><strong>Số tiền yêu cầu hoàn:</strong> {{ number_format($refundRequest->refund_amount, 0, ',', '.') }} ₫ ({{ $refundRequest->refund_percentage }}%)</p>
            <p style="margin: 5px 0;"><strong>Ngân hàng:</strong> {{ $refundRequest->bank_name }}</p>
            <p style="margin: 5px 0;"><strong>Chủ TK:</strong> {{ $refundRequest->account_name }}</p>
            <p style="margin: 5px 0;"><strong>Số TK:</strong> {{ $refundRequest->account_number }}</p>
        </div>

        <p>Vui lòng đăng nhập vào trang quản trị để xử lý yêu cầu này.</p>
        <a href="{{ route('admin.refunds.show', $refundRequest) }}" style="display: inline-block; padding: 10px 20px; background: #0275d8; color: #fff; text-decoration: none; border-radius: 5px;">Xem chi tiết yêu cầu</a>
        
        <p style="margin-top: 30px; font-size: 0.8em; color: #777;">Hệ thống quản lý Light Hotel</p>
    </div>
</body>
</html>
