<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Admin\RoomAdminController;
use App\Http\Controllers\AuthController;

// Trang chủ: danh sách phòng
Route::get('/', [RoomController::class, 'index'])->name('home');

// Chi tiết phòng + form đặt phòng
Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');

// Xử lý đặt phòng
Route::post('/rooms/{room}/book', [BookingController::class, 'store'])->name('bookings.store');

// Khu vực quản trị phòng (đơn giản, chưa có auth)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/rooms', [RoomAdminController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/create', [RoomAdminController::class, 'create'])->name('rooms.create');
    Route::post('/rooms', [RoomAdminController::class, 'store'])->name('rooms.store');
    Route::get('/rooms/{room}/edit', [RoomAdminController::class, 'edit'])->name('rooms.edit');
    Route::put('/rooms/{room}', [RoomAdminController::class, 'update'])->name('rooms.update');
    Route::delete('/rooms/{room}', [RoomAdminController::class, 'destroy'])->name('rooms.destroy');
});

// Auth đơn giản – chỉ form (chưa xử lý đăng nhập thật)
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');


