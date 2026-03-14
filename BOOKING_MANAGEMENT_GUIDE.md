# Hướng dẫn Quản lý Đặt Phòng (Booking Management)

## ✅ Đã triển khai

### 1. **Routes** 
[routes/web.php](routes/web.php) - Thêm 3 route POST:
- `POST /admin/bookings/{booking}/status` → `BookingAdminController@updateStatus`
- `POST /admin/bookings/{booking}/checkin` → `BookingAdminController@checkIn`
- `POST /admin/bookings/{booking}/checkout` → `BookingAdminController@checkOut`

### 2. **Controller Actions**
[app/Http/Controllers/Admin/BookingAdminController.php](app/Http/Controllers/Admin/BookingAdminController.php)

**Phương thức mới**:
- `updateStatus()` - Thay đổi trạng thái booking (pending → confirmed → completed/cancelled)
- `checkIn()` - Ghi nhận khách nhân phòng (chỉ khi `status='confirmed'`)
- `checkOut()` - Ghi nhận khách trả phòng (tự động đặt `status='completed'`)

Mỗi hành động tự động tạo **BookingLog** để lưu lịch sử.

### 3. **Model Helpers**
[app/Models/Booking.php](app/Models/Booking.php) - Thêm 2 method kiểm tra:
```php
public function isCheckinAllowed()    // true nếu status='confirmed' & chưa check-in
public function isCheckoutAllowed()  // true nếu đã check-in & chưa check-out
```

### 4. **Views Admin**
- [resources/views/admin/bookings/index.blade.php](resources/views/admin/bookings/index.blade.php) - Danh sách booking với form đổi trạng thái & button Check-in/Check-out
- [resources/views/admin/bookings/show.blade.php](resources/views/admin/bookings/show.blade.php) - Chi tiết booking + lịch sử thay đổi

### 5. **Database**
Các cột đã có trong schema:
- `bookings.status` (pending/confirmed/cancelled/completed)
- `bookings.actual_check_in` (datetime)
- `bookings.actual_check_out` (datetime)
- `booking_logs.old_status`, `new_status`, `changed_at`

---

## 🧪 Kiểm thử tính năng

### Manual Testing
1. **Truy cập admin**: `http://localhost/admin/bookings`
2. **Cập nhật trạng thái**: Chọn booking → dropdown status → "Cập nhật"
3. **Check-in**: Xác nhận booking → button "Check-in"
4. **Check-out**: Button "Check-out"
5. **Xem lịch sử**: Chi tiết booking → danh sách "Lịch sử thay đổi"

### Unit Tests (_test file mẫu_)
Tệp `tests/Feature/BookingAdminControllerTest.php` chứa 12 test case:
- ✅ Update status: pending→confirmed, confirmed→cancelled
- ✅ Validation invalid status
- ✅ Check-in required confirmed status
- ✅ Check-in success & logging
- ✅ Check-in fails if already checked in
- ✅ Check-out required checked-in
- ✅ Check-out success & marks completed
- ✅ Check-out fails if already checked out
- ✅ History tracking multiple changes
- ✅ Helper methods `isCheckinAllowed()`, `isCheckoutAllowed()`

**Chạy test** (nếu cấu hình DB test):
```bash
php artisan test tests/Feature/BookingAdminControllerTest.php
```

---

## 📋 Quy tắc nghiệp vụ

| Trạng thái | Check-in | Check-out | Hành động |
|-----------|----------|-----------|----------|
| pending | ❌ | ❌ | Chờ xác nhận |
| confirmed | ✅ | ❌ | Cho phép check-in |
| completed | ❌ | ❌ | Đã hoàn thành |
| cancelled | ❌ | ❌ | Đã hủy |

- **Check-in** chỉ thực hiện khi `status='confirmed'` & `actual_check_in` NULL
- **Check-out** chỉ thực hiện khi `actual_check_in` không NULL & `actual_check_out` NULL
- Check-out tự động đặt `status='completed'`

---

## 🔄 Flow Đơn Điển Hình

```
1. Booking tạo → status = 'pending'
2. Admin xác nhận → status = 'confirmed' [Log: pending→confirmed]
3. Admin check-in → actual_check_in = now() [Log: confirmed→checked_in]
4. Admin check-out → actual_check_out = now(), status = 'completed' [Log: →completed]
5. Xem lịch sử → Hiệu BookingLog với 3 dòng
```

---

## 📝 Tích hợp thêm (optional)

### Email Notification
Thêm vào `updateStatus()`, `checkIn()`, `checkOut()`:
```php
Mail::to($booking->user->email)->send(new BookingStatusChanged($booking));
```

### SMS Alert
Gọi API SMS provider khi check-in/check-out.

### Dashboard Widget
Thống kê: Booking pending, checked-in, completed hôm nay.

---

## 🛠️ Troubleshooting

**Issue**: Check-in button không xuất hiện
→ **Fix**: Kiểm tra `$booking->status === 'confirmed'` & `actual_check_in` NULL

**Issue**: Lịch sử không ghi log
→ **Fix**: Kiểm tra `BookingLog::create()` trong controller

**Issue**: Đơn hủy vẫn cho check-in
→ **Fix**: Controller kiểm tra `$booking->status !== 'confirmed'` → lỗi

---

## 📂 Tập tin được sửa đổi

```
✏️ app/Http/Controllers/Admin/BookingAdminController.php (thêm 3 method)
✏️ app/Models/Booking.php (thêm 2 helper)  
✏️ routes/web.php (thêm 3 route POST)
✏️ resources/views/admin/bookings/index.blade.php (thêm form & button)
✏️ resources/views/admin/bookings/show.blade.php (chi tiết & lịch sử)
✏️ database/factories/UserFactory.php (sửa field)
📝 tests/Feature/BookingAdminControllerTest.php (test file mẫu)
```

---

**Hoàn thành**: ✅ Quản lý đặt phòng cơ bản (status, check-in, check-out, logs)
