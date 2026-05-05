<?php

/**
 * Wizard đặt phòng (URL prefix /bookings): NewBookingController.
 * Trùng prefix với một số route BookingController trong web.php (POST /bookings …); Laravel ghép route theo thứ tự đăng ký — đổi URL cần kiểm tra route:list và link Blade.
 */
use App\Http\Controllers\NewBookingController;
use Illuminate\Support\Facades\Route;

Route::prefix('bookings')->name('bookings.')->group(function () {
    // Trang chủ đặt phòng
    Route::get('/', [NewBookingController::class, 'index'])->name('index');

    // Tìm phòng trống
    Route::post('/search', [NewBookingController::class, 'search'])->name('search');

    // Form đặt phòng
    Route::post('/booking-form', [NewBookingController::class, 'bookingForm'])->name('booking-form');

    // Xử lý đặt phòng
    Route::post('/store', [NewBookingController::class, 'store'])->name('internal.store');

    // Trang xác nhận
    Route::get('/confirmation/{booking}', [NewBookingController::class, 'confirmation'])->name('confirmation');
});
