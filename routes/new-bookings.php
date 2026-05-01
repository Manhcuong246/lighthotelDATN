<?php

use App\Http\Controllers\NewBookingController;
use Illuminate\Support\Facades\Route;

// Routes cho hệ thống đặt phòng mới
Route::prefix('bookings')->name('bookings.')->group(function () {
    // Trang chủ đặt phòng
    Route::get('/', [NewBookingController::class, 'index'])->name('index');

    // Tìm phòng trống
    Route::post('/search', [NewBookingController::class, 'search'])->name('search');

    // Form đặt phòng
    Route::post('/booking-form', [NewBookingController::class, 'bookingForm'])->name('booking-form');

    // Xử lý đặt phòng
    Route::post('/store', [NewBookingController::class, 'store'])->name('store');

    // Trang xác nhận
    Route::get('/confirmation/{booking}', [NewBookingController::class, 'confirmation'])->name('confirmation');
});
