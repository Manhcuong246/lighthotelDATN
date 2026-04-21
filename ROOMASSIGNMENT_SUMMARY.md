# Hệ thống Gán Phòng cho Khách (Room Assignment System)

## Tóm tắt

Hệ thống đã được cập nhật để cho phép gán phòng cụ thể (101, 102...) cho từng khách khi check-in,
thay vì chỉ lưu loại phòng (Standard, Deluxe...).

## Thay đổi Database

### Migration mới
**File:** `database/migrations/2026_04_18_000001_add_room_id_to_guests_table.php`

Thêm cột `room_id` vào bảng `guests` để lưu phòng cụ thể được gán khi check-in.

```bash
php artisan migrate
```

## Thay đổi Models

### 1. Guest Model (`app/Models/Guest.php`)

- Thêm `room_id` vào `$fillable`
- Thêm relationship `room()` để lấy thông tin phòng
- Thêm method `hasAssignedRoom()` kiểm tra đã gán phòng chưa
- Thêm accessor `room_display` trả về format "101 (Standard)"

### 2. Booking Model (`app/Models/Booking.php`)

- Thêm method `guestsByAssignedRoom()` - group guests theo room_id thực tế
- Thêm method `getAvailableRoomsForAssignment()` - lấy phòng trống có thể gán

## Thay đổi Controllers

### BookingAdminController (`app/Http/Controllers/Admin/BookingAdminController.php`)

#### API mới:

1. **`getGuestInfo()`** - Đã cập nhật
   - Trả về `guests_by_room` với thông tin room_id, room_assigned cho mỗi guest
   - Group theo phòng đã gán thực tế

2. **`assignGuestToRoom(Request $request, Booking $booking)`** - Mới
   - POST `/admin/bookings/{booking}/assign-room`
   - Gán phòng cụ thể cho khách
   - Body: `{ guest_id: 1, room_id: 5 }`

3. **`getAvailableRoomsForAssignment(Booking $booking)`** - Mới
   - GET `/admin/bookings/{booking}/available-rooms`
   - Trả về danh sách phòng trong booking có thể gán

## Thay đổi Routes

**File:** `routes/web.php`

Thêm 2 routes mới trong group admin:

```php
Route::post('/bookings/{booking}/assign-room', [BookingAdminController::class, 'assignGuestToRoom'])
    ->name('bookings.assign-room');
    
Route::get('/bookings/{booking}/available-rooms', [BookingAdminController::class, 'getAvailableRoomsForAssignment'])
    ->name('bookings.available-rooms');
```

## Thay đổi Views

### View mới: `_guests_by_room.blade.php`

**File:** `resources/views/admin/bookings/_guests_by_room.blade.php`

Partial view hiển thị:
- Danh sách khách nhóm theo phòng đã gán
- Dropdown chọn phòng cho khách chưa gán
- Badge trạng thái: "Đã gán" / "Chưa gán"
- Thống kê: Tổng khách, đã gán phòng, chưa gán, đã xác nhận
- JavaScript xử lý gán phòng realtime

## Cách sử dụng

### 1. Chạy migration

```bash
php artisan migrate
```

### 2. Trong Blade view (check-in modal)

```blade
{{-- Include partial view --}}
@include('admin.bookings._guests_by_room', [
    'booking' => $booking,
    'guestsByRoom' => $booking->guestsByAssignedRoom()
])
```

### 3. Hoặc dùng JavaScript fetch API

```javascript
// Load danh sách phòng có thể gán
fetch(`/admin/bookings/${bookingId}/available-rooms`)
    .then(r => r.json())
    .then(data => {
        console.log(data.rooms); // [{id, room_number, room_type, max_guests, current_guests}]
    });

// Gán phòng cho khách
fetch(`/admin/bookings/${bookingId}/assign-room`, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify({ guest_id: 1, room_id: 5 })
})
.then(r => r.json())
.then(data => {
    console.log(data.guest.room_display); // "101 (Standard)"
});
```

## Flow nghiệp vụ

1. **Khách đặt phòng**: Chỉ chọn loại phòng (Standard) và số lượng
2. **Lưu booking**: Tạo booking_rooms liên kết với room_type, chưa có room cụ thể
3. **Khi check-in**:
   - Hiển thị danh sách khách
   - Cho phép chọn phòng cụ thể (101, 102...) cho từng khách
   - Lưu room_id vào bảng guests
4. **Hiển thị**: Nhóm khách theo phòng đã gán

## API Response Format

### GET /admin/bookings/{booking}/guest-info

```json
{
    "booking": {
        "id": 123,
        "user_name": "Nguyễn Văn A",
        "check_in": "18/04/2026",
        "check_out": "20/04/2026"
    },
    "guests_by_room": [
        {
            "room_id": 5,
            "room_name": "101 (Standard)",
            "room_number": "101",
            "room_type": "Standard",
            "guests": [
                {
                    "id": 1,
                    "name": "Nguyễn Văn A",
                    "type": "adult",
                    "cccd": "012345678901",
                    "status": "pending",
                    "room_id": 5,
                    "room_assigned": true
                }
            ]
        },
        {
            "room_id": null,
            "room_name": "Chưa gán phòng",
            "guests": [
                {
                    "id": 2,
                    "name": "Trần Thị B",
                    "room_id": null,
                    "room_assigned": false
                }
            ]
        }
    ]
}
```

### POST /admin/bookings/{booking}/assign-room

Request:
```json
{
    "guest_id": 1,
    "room_id": 5
}
```

Response:
```json
{
    "success": true,
    "message": "Đã gán phòng thành công",
    "guest": {
        "id": 1,
        "name": "Nguyễn Văn A",
        "room_display": "101 (Standard)"
    }
}
```

## Lưu ý quan trọng

1. **room_id trong guests là nullable**: Vì khi đặt phòng chưa biết gán phòng nào
2. **Chỉ gán phòng trong booking**: API kiểm tra room_id có thuộc booking không
3. **Group by room_id thực tế**: Khác với room_index (0,1,2), giờ group theo room_id (5,6,7...)
4. **Tương thích ngược**: Code cũ dùng room_index vẫn hoạt động

## Test sau khi cập nhật

```bash
# 1. Chạy migration
php artisan migrate

# 2. Clear cache
php artisan view:clear
php artisan route:clear
php artisan cache:clear

# 3. Test API
curl http://127.0.0.1:8000/admin/bookings/1/guest-info
```

## Files đã sửa

1. `database/migrations/2026_04_18_000001_add_room_id_to_guests_table.php` - Migration mới
2. `app/Models/Guest.php` - Thêm room relationship
3. `app/Models/Booking.php` - Thêm guestsByAssignedRoom()
4. `app/Http/Controllers/Admin/BookingAdminController.php` - Thêm API gán phòng
5. `routes/web.php` - Thêm routes mới
6. `resources/views/admin/bookings/_guests_by_room.blade.php` - View hiển thị (mới)
