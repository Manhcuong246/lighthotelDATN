<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Đặt phòng #{{ $booking->id }}</title>
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #1e293b;">
    <p>Xin chào <strong>{{ $booking->user?->full_name ?? 'Quý khách' }}</strong>,</p>
    <p>{{ $messageLine }}</p>
    <p><strong>Mã đơn:</strong> #{{ $booking->id }}</p>
    <p><a href="{{ url('/account/bookings/'.$booking->id) }}">Xem chi tiết đơn</a></p>
    <p>Trân trọng,<br>{{ config('app.name') }}</p>
</body>
</html>
