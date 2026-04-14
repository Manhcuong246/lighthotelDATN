# Light Hotel Booking System

Hệ thống quản lý đặt phòng khách sạn xây dựng bằng Laravel 12.

## Tính năng chính

- 🔐 Đăng ký/Đăng nhập người dùng
- 🏨 Quản lý phòng và loại phòng
- 📅 Đặt phòng và quản lý booking
- 💳 Thanh toán VNPay
- 📧 Gửi email xác nhận
- 👨‍💼 Admin dashboard
- ⭐ Đánh giá và review
- 💰 Quản lý hóa đơn
- 🔄 Xử lý refund

## Công nghệ

- **Backend:** Laravel 12, PHP 8.2+
- **Database:** MySQL
- **Frontend:** Blade Templates, Bootstrap
- **Payment:** VNPay Sandbox
- **Email:** SMTP (Gmail)

## Cài đặt

```bash
# Clone repository
git clone https://github.com/Manhcuong246/lighthotelDATN.git
cd lighthotelDATN

# Cài đặt dependencies
composer install

# Tạo file .env
cp .env.example .env

# Tạo application key
php artisan key:generate

# Cấu hình database trong .env
# Sau đó chạy migration
php artisan migrate

# Chạy development server
php artisan serve
```

## Truy cập

- **Trang chủ:** http://127.0.0.1:8000
- **Admin:** http://127.0.0.1:8000/admin

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
