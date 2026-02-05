<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Admin\RoomAdminController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BookingAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Admin\ReviewAdminController;
use App\Http\Controllers\Admin\PaymentAdminController;
use App\Http\Controllers\Admin\SettingsAdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountController;

Route::get('/', [RoomController::class, 'index'])->name('home');

Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');

Route::post('/rooms/{room}/book', [BookingController::class, 'store'])->name('bookings.store');

Route::get('/admin/login', [AdminController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');

Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/rooms', [RoomAdminController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/create', [RoomAdminController::class, 'create'])->name('rooms.create');
    Route::post('/rooms', [RoomAdminController::class, 'store'])->name('rooms.store');
    Route::get('/rooms/{room}/edit', [RoomAdminController::class, 'edit'])->name('rooms.edit');
    Route::put('/rooms/{room}', [RoomAdminController::class, 'update'])->name('rooms.update');
    Route::delete('/rooms/{room}', [RoomAdminController::class, 'destroy'])->name('rooms.destroy');
    
    Route::get('/bookings', [BookingAdminController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [BookingAdminController::class, 'show'])->name('bookings.show');
    Route::get('/bookings/{booking}/edit', [BookingAdminController::class, 'edit'])->name('bookings.edit');
    Route::put('/bookings/{booking}', [BookingAdminController::class, 'update'])->name('bookings.update');
    Route::delete('/bookings/{booking}', [BookingAdminController::class, 'destroy'])->name('bookings.destroy');
    
    Route::get('/users', [UserAdminController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserAdminController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserAdminController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserAdminController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserAdminController::class, 'destroy'])->name('users.destroy');
    
    Route::get('/reviews', [ReviewAdminController::class, 'index'])->name('reviews.index');
    Route::get('/reviews/{review}', [ReviewAdminController::class, 'show'])->name('reviews.show');
    Route::get('/reviews/{review}/edit', [ReviewAdminController::class, 'edit'])->name('reviews.edit');
    Route::put('/reviews/{review}', [ReviewAdminController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewAdminController::class, 'destroy'])->name('reviews.destroy');
    
    Route::get('/payments', [PaymentAdminController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}', [PaymentAdminController::class, 'show'])->name('payments.show');
    Route::get('/payments/{payment}/edit', [PaymentAdminController::class, 'edit'])->name('payments.edit');
    Route::put('/payments/{payment}', [PaymentAdminController::class, 'update'])->name('payments.update');
    Route::delete('/payments/{payment}', [PaymentAdminController::class, 'destroy'])->name('payments.destroy');
    
    Route::get('/settings', [SettingsAdminController::class, 'index'])->name('settings.index');
    Route::put('/settings/general', [SettingsAdminController::class, 'updateGeneral'])->name('settings.update.general');
    Route::put('/settings/site-content', [SettingsAdminController::class, 'updateSiteContent'])->name('settings.update.site.content');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/bookings', [AccountController::class, 'bookings'])->name('bookings');
    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
    Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::get('/settings', [AccountController::class, 'settings'])->name('settings');
    Route::put('/settings', [AccountController::class, 'updateSettings'])->name('settings.update');
});


