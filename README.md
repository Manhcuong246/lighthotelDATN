<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Light Hotel Booking System

A comprehensive hotel booking management system built with Laravel 12.

### Features

- 🏨 **Room Management** - Manage room types, pricing, and availability
- 📅 **Booking System** - Single and multi-room bookings
- 💳 **Payment Integration** - VNPay payment gateway support
- 👥 **Guest Management** - Track guests with occupancy-based pricing
- 📧 **Email Notifications** - Automated payment instructions
- 📊 **Admin Dashboard** - Complete booking management interface
- 💰 **Surcharge Calculation** - Automatic pricing for extra guests
- 🎫 **Coupon System** - Discount and promotion support
- ⭐ **Review System** - Guest reviews and ratings

### VNPay Payment Feature

When customers select VNPay payment method:
- Automatic email with detailed booking information
- Secure signed payment links
- 15-minute payment session timeout
- 14-day link validity period
- Real-time payment status tracking

### Guest Capacity & Pricing

- Standard capacity: 3 guests per room
- Maximum capacity: 6 guests per room
- Children 0-5 years: Free (occupies slot)
- Children 6-11 years: 12.5% surcharge
- Adults: 25% surcharge (when exceeding standard capacity)

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Installation

### Requirements

- PHP >= 8.2
- Composer
- MySQL/MariaDB
- Node.js & NPM

### Quick Start

```bash
# Clone repository
git clone https://github.com/Manhcuong246/lighthotelDATN.git
cd lighthotelDATN

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database in .env
DB_DATABASE=lighthotel
DB_USERNAME=root
DB_PASSWORD=

# Run migrations
php artisan migrate --seed

# Start development server
php artisan serve
```

### Email Configuration

To enable VNPay payment emails:

1. Create Gmail App Password: https://myaccount.google.com/apppasswords
2. Update `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Light Hotel"
```

3. Clear cache:
```bash
php artisan config:clear
php artisan cache:clear
```

## Admin Features

### Booking Management

- **Create Single Booking**: Create booking for individual guests
- **Create Multi-Room Booking**: Book multiple rooms at once
- **Occupancy-Based Pricing**: Automatic surcharge calculation
- **Payment Methods**: Cash or VNPay
- **Status Tracking**: Separate booking and payment status

### Payment Status Flow

1. **Unpaid** → Initial state
2. **Deposited** → Partial payment received
3. **Paid** → Full payment completed

### Booking Status Flow

1. **Pending** → Awaiting confirmation
2. **Confirmed** → Booking confirmed

## Testing

```bash
# Run test suite
php artisan test

# Test VNPay email (after email config)
php test_vnpay_email.php
```

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
