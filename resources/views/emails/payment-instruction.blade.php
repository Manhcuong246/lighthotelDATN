<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Thông tin thanh toán - {{ $hotelInfo->name ?? 'Light Hotel' }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .booking-info {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .booking-info h3 {
            margin: 0 0 15px 0;
            color: #1e3a8a;
            font-size: 18px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #e2e8f0;
        }
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .info-label {
            color: #64748b;
        }
        .info-value {
            font-weight: 600;
            color: #1e293b;
        }
        .payment-box {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 2px solid #f59e0b;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            margin-bottom: 25px;
        }
        .payment-box h2 {
            margin: 0 0 20px 0;
            color: #92400e;
            font-size: 20px;
        }
        .amount {
            font-size: 32px;
            font-weight: 700;
            color: #dc2626;
            margin-bottom: 20px;
        }
        .bank-info {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            text-align: left;
            margin-bottom: 20px;
        }
        .bank-info-row {
            display: flex;
            margin-bottom: 12px;
        }
        .bank-info-row:last-child {
            margin-bottom: 0;
        }
        .bank-label {
            width: 120px;
            color: #64748b;
            font-weight: 500;
        }
        .bank-value {
            flex: 1;
            font-weight: 600;
            color: #1e293b;
        }
        .transfer-content {
            background-color: #dbeafe;
            border: 2px dashed #3b82f6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
        }
        .transfer-content-label {
            color: #1e40af;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .transfer-content-value {
            font-size: 20px;
            font-weight: 700;
            color: #1e40af;
            font-family: monospace;
        }
        .qr-section {
            text-align: center;
            margin-bottom: 25px;
        }
        .qr-section h4 {
            color: #1e3a8a;
            margin-bottom: 15px;
        }
        .qr-code {
            display: inline-block;
            padding: 15px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .qr-code img {
            max-width: 250px;
            height: auto;
        }
        .note {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 25px;
        }
        .note-title {
            color: #dc2626;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .note-content {
            color: #7f1d1d;
            font-size: 14px;
            line-height: 1.6;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            margin: 0 0 8px 0;
            color: #64748b;
            font-size: 14px;
        }
        .hotel-name {
            font-weight: 600;
            color: #1e3a8a;
        }
        .contact-info {
            margin-top: 10px;
            font-size: 13px;
        }
        .highlight {
            color: #dc2626;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Thông Tin Thanh Toán</h1>
            <p>{{ $hotelInfo->name ?? 'Light Hotel' }}</p>
        </div>

        <div class="content">
            <div class="booking-info">
                <h3>Chi tiết đơn đặt phòng #{{ $booking->id }}</h3>
                <div class="info-row">
                    <span class="info-label">Khách hàng:</span>
                    <span class="info-value">{{ $booking->user->full_name ?? $booking->user->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $booking->user->email }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Ngày nhận phòng:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Ngày trả phòng:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Số đêm:</span>
                    <span class="info-value">{{ $nights }} đêm</span>
                </div>
            </div>

            <div class="payment-box">
                <h2>Số tiền cần thanh toán</h2>
                <div class="amount">{{ number_format($booking->total_price, 0, ',', '.') }}đ</div>

                <div class="bank-info">
                    <div class="bank-info-row">
                        <span class="bank-label">Ngân hàng:</span>
                        <span class="bank-value">{{ $hotelInfo->bank_name ?? 'Vietcombank' }}</span>
                    </div>
                    <div class="bank-info-row">
                        <span class="bank-label">Số tài khoản:</span>
                        <span class="bank-value">{{ $hotelInfo->bank_account ?? '0326083913' }}</span>
                    </div>
                    <div class="bank-info-row">
                        <span class="bank-label">Chủ tài khoản:</span>
                        <span class="bank-value">{{ $hotelInfo->bank_account_name ?? 'LE DUC TRUNG' }}</span>
                    </div>
                </div>

                <div class="transfer-content">
                    <div class="transfer-content-label">Nội dung chuyển khoản</div>
                    <div class="transfer-content-value">BOOKING {{ $booking->id }}</div>
                </div>

                @if($qrCodeUrl)
                <div class="qr-section">
                    <h4>Quét mã QR để thanh toán nhanh</h4>
                    <div class="qr-code">
                        <img src="{{ $qrCodeUrl }}" alt="QR Code">
                    </div>
                </div>
                @endif
            </div>

            <div class="note">
                <div class="note-title">Lưu ý quan trọng:</div>
                <div class="note-content">
                    <p>1. Vui lòng chuyển khoản đúng số tiền và nội dung như trên.</p>
                    <p>2. Sau khi chuyển khoản, vui lòng gửi ảnh chụp màn hình giao dịch qua Zalo/Email để được xác nhận nhanh nhất.</p>
                    <p>3. Đơn đặt phòng sẽ được giữ trong vòng <span class="highlight">24 giờ</span> kể từ khi tạo. Sau thời gian này nếu chưa thanh toán, đơn có thể bị hủy.</p>
                </div>
            </div>
        </div>

        <div class="footer">
            <p class="hotel-name">{{ $hotelInfo->name ?? 'Light Hotel' }}</p>
            <p>Hotline: {{ $hotelInfo->phone ?? '1900 xxxx' }} | Email: {{ $hotelInfo->email ?? 'booking@lighthouse.com' }}</p>
            <p class="contact-info">Địa chỉ: {{ $hotelInfo->address ?? 'Đà Nẵng, Việt Nam' }}</p>
        </div>
    </div>
</body>
</html>
