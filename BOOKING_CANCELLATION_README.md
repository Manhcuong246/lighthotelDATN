# Chức năng Hủy Booking và Hoàn Tiền

## Tổng quan

Chức năng này cho phép người dùng hủy booking và nhận lại tiền theo chính sách dựa trên thời gian hủy so với thời gian nhận phòng.

## Business Logic

### Chính sách hủy

1. **Hủy trước hơn 24 giờ so với check-in**
   - ✅ Cho phép hủy
   - 💰 Hoàn 100% tiền
   - 📝 `payment_status` = `refunded`
   - 📝 `refund_type` = `full`

2. **Hủy trong vòng 24 giờ trước check-in**
   - ✅ Cho phép hủy
   - 💰 Hoàn 50% tiền
   - 📝 `payment_status` = `partial_refunded`
   - 📝 `refund_type` = `partial`

3. **Hủy sau thời gian check-in**
   - ✅ Vẫn cho phép hủy (để quản lý)
   - 💰 Không hoàn tiền
   - 📝 `payment_status` = `refunded`
   - 📝 `refund_type` = `none`

## Files được tạo/sửa

### 1. Database
- **Migration**: `2026_04_02_000000_create_refund_logs_table.php`
- **Model**: `RefundLog.php`
- **Model Update**: `Booking.php` (thêm fields và relationships)

### 2. Service Layer
- **Service**: `BookingCancellationService.php`
  - `cancelBooking()` - Xử lý logic hủy chính
  - `calculateRefund()` - Tính toán tiền hoàn
  - `getCancellationPolicy()` - Lấy chính sách hiện tại

### 3. Controller
- **Controller**: `BookingCancellationController.php`
  - `show()` - Hiển thị trang xác nhận hủy
  - `cancel()` - Xử lý AJAX hủy booking
  - `getPolicy()` - API lấy chính sách hủy
  - `adminCancel()` - Admin hủy booking

### 4. Views
- **View**: `bookings/cancel.blade.php` - Trang xác nhận hủy
- **View**: `bookings/show.blade.php` - Trang chi tiết booking

### 5. Routes
```php
// User routes
Route::get('/bookings/{booking}/cancel', [BookingCancellationController::class, 'show'])->name('bookings.cancel');
Route::post('/bookings/{booking}/cancel', [BookingCancellationController::class, 'cancel'])->name('bookings.cancel.post');
Route::get('/bookings/{booking}/policy', [BookingCancellationController::class, 'getPolicy'])->name('bookings.policy');
```

## Database Schema

### Bảng `refund_logs`
```sql
CREATE TABLE refund_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    booking_id BIGINT NOT NULL,
    refund_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    refund_type ENUM('full', 'partial', 'none') NOT NULL DEFAULT 'none',
    reason TEXT NULL,
    processed_by BIGINT NULL,
    refunded_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (booking_id),
    INDEX (refund_type),
    INDEX (refunded_at)
);
```

### Bảng `bookings` (fields mới)
```sql
ALTER TABLE bookings ADD COLUMN check_in_date DATETIME NULL;
ALTER TABLE bookings ADD COLUMN check_out_date DATETIME NULL;
ALTER TABLE bookings ADD COLUMN payment_status VARCHAR(20) DEFAULT 'pending';
ALTER TABLE bookings ADD COLUMN cancellation_reason TEXT NULL;
ALTER TABLE bookings ADD COLUMN cancelled_at TIMESTAMP NULL;
```

## API Endpoints

### 1. Lấy chính sách hủy
```
GET /bookings/{id}/policy
Response: {
    "success": true,
    "policy": {
        "current_time": "02/04/2026 11:30",
        "check_in_time": "04/04/2026 14:00",
        "hours_until_check_in": 48,
        "can_cancel": true,
        "refund_percentage": 100,
        "refund_amount": 1500000,
        "policy_text": "Hoàn 100% tiền nếu hủy trước hơn 24 giờ..."
    }
}
```

### 2. Hủy booking
```
POST /bookings/{id}/cancel
Body: {
    "reason": "Lý do hủy booking..."
}
Response: {
    "success": true,
    "message": "Hủy thành công. Hoàn lại 100% số tiền (1.500.000 ₫).",
    "refund_amount": 1500000,
    "refund_type": "full",
    "redirect_url": "/bookings/123"
}
```

## Flow sử dụng

### User Flow
1. User vào trang chi tiết booking
2. Click "Hủy Booking"
3. Hệ thống hiển thị trang xác nhận với chính sách hủy
4. User nhập lý do và xác nhận
5. Hệ thống xử lý và hiển thị kết quả
6. User được redirect về trang chi tiết với thông tin hủy

### Admin Flow
1. Admin có thể hủy booking bất kỳ
2. Admin phải nhập lý do bắt buộc
3. Hệ thống ghi log admin đã xử lý

## Edge Cases được xử lý

1. **Booking không tồn tại** → Throw exception
2. **Booking đã hủy** → Throw exception
3. **Invalid datetime** → Carbon xử lý lỗi
4. **Database transaction** → Rollback nếu có lỗi
5. **Logging** → Ghi log cho debugging

## Security

1. **Authentication** → Middleware kiểm tra user login
2. **Authorization** → Chỉ owner của booking hoặc admin có thể hủy
3. **CSRF** → Validate token trên form submit
4. **SQL Injection** → Eloquent ORM bảo vệ

## Testing

Test cases cần kiểm tra:
1. Hủy trước 24 giờ → 100% refund
2. Hủy trong 24 giờ → 50% refund
3. Hủy sau check-in → 0% refund
4. Hủy booking không tồn tại → Error
5. Hủy booking đã hủy → Error
6. Invalid booking ID → Error

## Frontend Integration

View sử dụng:
- **Bootstrap 5** cho UI
- **SweetAlert2** cho notifications
- **AJAX** cho form submit
- **Carbon** cho datetime formatting

## Logging

Tất cả operations được log:
- Success: `Log::info()` với refund details
- Error: `Log::error()` với exception details
- Database: Transaction logs

## Notes

- Sử dụng `Carbon::now()` để lấy thời gian server
- Format currency theo chuẩn Việt Nam (1.500.000 ₫)
- Responsive design cho mobile/desktop
- Accessible markup cho screen readers
