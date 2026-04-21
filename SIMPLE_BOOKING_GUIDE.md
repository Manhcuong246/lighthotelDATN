# Hướng dẫn Form Đặt Phòng Đơn Giản (Simple Booking)

## Tổng quan

Hệ thống cho phép đặt phòng với form đơn giản:
- Chọn số người → Tự động render form tương ứng
- Người đại diện (người 1): name và cccd **bắt buộc**
- Người còn lại: name và cccd **có thể để trống** (NULL)

## Files đã tạo

### 1. Migration (chạy để cập nhật database)
```bash
php artisan migrate
```

**File:** `database/migrations/2026_04_18_000002_update_guests_table_for_representative.php`

Thay đổi:
- Sửa `name` thành `nullable`
- Thêm cột `is_representative` (boolean)

### 2. Blade View
**File:** `resources/views/bookings/create-simple.blade.php`

Form đặt phòng với:
- Chọn số người
- Render động các form nhập thông tin
- Người 1 có badge "Người đại diện" và các trường bắt buộc

### 3. Form Request Validation
**File:** `app/Http/Requests/StoreSimpleBookingRequest.php`

Validation rules:
```php
// Người đại diện (guests.0)
'guests.0.name' => 'required|string|max:150|min:2'
'guests.0.cccd' => 'required|string|size:12|regex:/^[0-9]{12}$/'

// Người thứ 2,3... (guests.1, guests.2...)
'guests.1.name' => 'nullable|string|max:150'
'guests.1.cccd' => 'nullable|string|size:12|regex:/^[0-9]{12}$/'
```

### 4. Controller Methods
**File:** `app/Http/Controllers/BookingController.php`

Thêm 2 method:
- `createSimple()` - Hiển thị form
- `storeSimple()` - Xử lý lưu booking và guests

### 5. Routes
**File:** `routes/web.php`

```php
GET  /bookings/simple/create  → createSimple()
POST /bookings/simple         → storeSimple()
```

## Cách sử dụng

### 1. Link đến form đặt phòng

```html
<a href="{{ route('bookings.create-simple', [
    'room_id' => $room->id,
    'check_in' => '2026-04-20',
    'check_out' => '2026-04-22'
]) }}" class="btn btn-primary">
    Đặt phòng ngay
</a>
```

### 2. Dữ liệu được lưu

**Bảng bookings:**
```php
[
    'user_id'     => auth()->id() hoặc null,
    'check_in'    => '2026-04-20',
    'check_out'   => '2026-04-22',
    'adults'      => 3,
    'total_price' => 1000000,
    'status'      => 'pending'
]
```

**Bảng guests:**
```php
// Người đại diện
[
    'booking_id'       => 1,
    'name'             => 'Nguyễn Văn A',
    'cccd'             => '012345678901',
    'type'             => 'adult',
    'is_representative'=> true,
    'checkin_status'   => 'pending'
]

// Người thứ 2 (chưa có thông tin)
[
    'booking_id'       => 1,
    'name'             => null,      // ← NULL
    'cccd'             => null,      // ← NULL
    'type'             => 'adult',
    'is_representative'=> false,
    'checkin_status'   => 'pending'
]
```

### 3. Kiểm tra người đại diện trong Model

```php
// Lấy người đại diện
$representative = $booking->guests()
    ->where('is_representative', true)
    ->first();

// Lấy những người chưa có thông tin đầy đủ
$incompleteGuests = $booking->guests()
    ->where(function ($q) {
        $q->whereNull('name')
          ->orWhereNull('cccd');
    })
    ->get();
```

## Luồng nghiệp vụ

### Khi đặt phòng online:
```
Người dùng chọn phòng 
    → Chọn số người 
    → Nhập thông tin người đại diện 
    → Submit
```

### Khi check-in tại khách sạn:
```
Nhân viên mở booking 
    → Thấy danh sách khách 
    → Người chưa có thông tin hiển thị "Chưa có" 
    → Nhân viên nhập bổ sung 
    → Cập nhật vào database (name, cccd bắt buộc lúc này)
```

## JavaScript trong Blade

```javascript
// Render form động khi thay đổi số người
adultsSelect.addEventListener('change', function() {
    renderGuestForms(parseInt(this.value));
});

// Người 1: required
// Người 2+: nullable
```

## Validation Error Messages

| Field | Lỗi | Message |
|-------|-----|---------|
| guests.0.name | required | Vui lòng nhập tên người đại diện |
| guests.0.cccd | required | Vui lòng nhập CCCD người đại diện |
| guests.0.cccd | size:12 | CCCD phải đúng 12 số |
| guests.*.cccd | regex | CCCD chỉ được chứa 12 chữ số |

## Test

```bash
# 1. Chạy migration
php artisan migrate

# 2. Clear cache
php artisan route:clear
php artisan view:clear

# 3. Truy cập form
# /bookings/simple/create?room_id=1&check_in=2026-04-20&check_out=2026-04-22
```

## Ghi chú quan trọng

1. **name nullable**: Cho phép để trống khi đặt phòng online
2. **is_representative**: Luôn set người đầu tiên là true
3. **checkin_status**: Mặc định 'pending', sẽ cập nhật khi check-in
4. **Transaction**: Sử dụng DB::transaction() đảm bảo toàn vẹn dữ liệu
