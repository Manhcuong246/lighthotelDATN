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

    // Test form
    Route::get('/test', function() {
        return view('bookings.test-form');
    })->name('test-form');

    // Simple test
    Route::get('/simple', function() {
        return view('bookings.test-simple');
    })->name('simple-test');

    // Xử lý đặt phòng
    Route::post('/store', [NewBookingController::class, 'store'])->name('store');

    // Trang xác nhận
    Route::get('/confirmation/{booking}', [NewBookingController::class, 'confirmation'])->name('confirmation');
});

// Admin routes
Route::prefix('admin/bookings')->name('admin.bookings.')->middleware(['auth', 'admin'])->group(function () {
    // Chi tiết booking
    Route::get('/{booking}', [NewBookingController::class, 'show'])->name('show');

    // Check-in
    Route::post('/{booking}/checkin', [NewBookingController::class, 'checkIn'])->name('checkin');
    Route::patch('/guests/{guest}/toggle-status', [\App\Http\Controllers\Admin\BookingAdminController::class, 'toggleGuestStatus'])->name('guests.toggle-status');

    // Check-out
    Route::post('/{booking}/checkout', [NewBookingController::class, 'checkOut'])->name('checkout');
});
