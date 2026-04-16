# Hướng dẫn cấu hình Email SMTP

## ⚠️ Lỗi hiện tại:
Email không gửi được vì `MAIL_FROM_ADDRESS` trống trong `.env`

## ✅ Cách sửa:

### 1. Mở file `.env` và cập nhật:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com          # ← Điền email của bạn
MAIL_PASSWORD=your-app-password              # ← Điền App Password (KHÔNG phải password thường)
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com       # ← Phải điền (giống MAIL_USERNAME)
MAIL_FROM_NAME="Light Hotel"
```

### 2. Tạo Gmail App Password:

1. Vào: https://myaccount.google.com/security
2. Bật **2-Step Verification** (nếu chưa bật)
3. Vào: https://myaccount.google.com/apppasswords
4. Chọn **App name**: "Light Hotel"
5. Click **Create**
6. Copy password (16 ký tự) và dán vào `MAIL_PASSWORD`

### 3. Xóa cache config:

```bash
php artisan config:clear
php artisan cache:clear
```

### 4. Test lại:

```bash
php test_vnpay_email.php
```

## 📧 Email sẽ được gửi khi:
- Admin tạo đơn với phương thức VNPay
- Payment status = "pending"
- Email khách có trong hệ thống

## 🔍 Kiểm tra log:
```bash
# Xem log mới nhất
Get-Content storage\logs\laravel.log -Tail 50

# Hoặc trên Linux/Mac
tail -f storage/logs/laravel.log
```

## ✅ Khi thành công:
- Log sẽ ghi: `VNPay payment email sent successfully`
- Khách nhận được email với chi tiết đặt phòng
- Email chứa link thanh toán VNPay
