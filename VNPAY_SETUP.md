# Hướng Dẫn Cấu Hình VNPay

## 📋 Các bước đăng ký và cấu hình VNPay Sandbox

### Bước 1: Đăng ký tài khoản VNPay Sandbox
1. Truy cập: https://sandbox.vnpayment.vn/
2. Đăng ký tài khoản merchant
3. Sau khi đăng ký xong, bạn sẽ nhận được:
   - **TMN Code** (Mã TMN)
   - **Hash Secret** (Mã bí mật)

### Bước 2: Cấu hình trong .env

Mở file `.env` và cập nhật thông tin:

```env
VNPAY_TMN_CODE=YOUR_TMN_CODE_HERE
VNPAY_HASH_SECRET=YOUR_HASH_SECRET_HERE
VNPAY_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html
```

Thay thế:
- `YOUR_TMN_CODE_HERE` bằng mã TMN bạn nhận được
- `YOUR_HASH_SECRET_HERE` bằng hash secret bạn nhận được

### Bước 3: Cấu hình Return URL (IPN)

VNPay cần callback URL công khai để gửi kết quả thanh toán về.

**Lưu ý quan trọng:**
- Khi test ở local, bạn cần dùng ngrok hoặc similar tool để có HTTPS URL
- Hoặc deploy lên server thật có SSL

#### Sử dụng ngrok (cho development):

```bash
# Cài đặt ngrok nếu chưa có
# Download từ: https://ngrok.com/

# Start Laravel
php artisan serve

# Trong terminal khác, chạy ngrok
ngrok http 8000
```

Ngrok sẽ cho bạn URL dạng: `https://xxxx-xxxx.ngrok.io`

Sau đó cấu hình trong VNPay Sandbox:
- **Return URL**: `https://xxxx-xxxx.ngrok.io/payment/callback`

### Bước 4: Test thanh toán

1. Tạo booking mới ở status `pending`
2. Admin click "💳 Yêu cầu thanh toán"
3. User vào lịch sử đặt phòng → Click "Thanh toán 30%"
4. Chọn phương thức **VNPay**
5. Hệ thống redirect sang VNPay
6. Thanh toán thành công → Redirect về website với status `confirmed`

---

## 🔧 API Endpoints

### 1. Payment Form
- **URL**: `/bookings/{booking}/payment`
- **Method**: GET
- **Description**: Hiển thị form chọn phương thức thanh toán

### 2. Process Payment
- **URL**: `/bookings/{booking}/payment`
- **Method**: POST
- **Parameters**:
  - `payment_method`: vnpay|momo|bank_transfer
- **Description**: Xử lý thanh toán

### 3. VNPay Callback
- **URL**: `/payment/callback`
- **Method**: GET/POST
- **Description**: Nhận kết quả từ VNPay

---

## 📊 Response Codes từ VNPay

| Code | Ý nghĩa |
|------|---------|
| 00 | Thành công ✅ |
| 07 | Thẻ bị khóa |
| 09 | Thẻ hết hạn |
| 10 | Số dư không đủ |
| 11 | Vượt hạn mức ngày |
| 24 | Hủy giao dịch |
| 51 | Sai thông tin thẻ |
| 65 | Tài khoản không tồn tại |
| 79 | Giao dịch thất bại |
| 99 | Lỗi hệ thống |

---

## 🎯 Flow Hoạt Động

```
User đặt phòng
    ↓
Status: pending
    ↓
Admin: "Yêu cầu thanh toán"
    ↓
Status: awaiting_payment
    ↓
User: Click "Thanh toán 30%"
    ↓
Chọn VNPay
    ↓
Redirect → VNPay Gateway
    ↓
User nhập thông tin thanh toán
    ↓
VNPay xử lý
    ↓
Callback → /payment/callback
    ↓
✅ Thành công: Status = confirmed
❌ Thất bại: Status = awaiting_payment (giữ nguyên để thử lại)
```

---

## ⚠️ Lưu Ý Quan Trọng

1. **Bảo mật Hash Secret**: Không commit file `.env` lên Git
2. **HTTPS Required**: Production bắt buộc phải có SSL
3. **Logging**: Kiểm tra `storage/logs/laravel.log` khi có lỗi
4. **Timeout**: Set timeout phù hợp cho payment gateway
5. **Idempotency**: Cùng 1 booking không nên tạo nhiều payment URL

---

## 🐛 Troubleshooting

### Lỗi: "Giao dịch không hợp lệ"
- Kiểm tra Hash Secret trong .env
- Đảm bảo clock server đồng bộ (timezone)

### Lỗi: "Có lỗi xảy ra khi chuyển hướng đến VNPay"
- Kiểm tra TMN Code
- Kiểm tra kết nối internet
- Xem log chi tiết trong `storage/logs/laravel.log`

### Callback không hoạt động
- Đảm bảo URL công khai (dùng ngrok cho local)
- Kiểm tra firewall không block VNPay IP
- Verify route `/payment/callback` tồn tại

---

## 📞 Hỗ Trợ

- VNPay Sandbox Support: support@vnpayment.vn
- Documentation: https://sandbox.vnpayment.vn/apis/
