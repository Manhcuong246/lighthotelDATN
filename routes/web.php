<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\BookingCancellationController;
use App\Http\Controllers\Admin\RoomAdminController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BookingAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Admin\ReviewAdminController;
use App\Http\Controllers\Admin\PaymentAdminController;
use App\Http\Controllers\Admin\CouponAdminController;
use App\Http\Controllers\Admin\SettingsAdminController;
use App\Http\Controllers\Admin\InvoiceAdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\VnPayController;
use App\Http\Controllers\Admin\RoomTypeController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\RefundAdminController;
use App\Http\Controllers\GuestCheckInController;



// Serve storage files (fallback when symlink fails or PHP built-in server)
Route::get('/storage/{path}', function (string $path) {
    $path = str_replace(['../', '..\\'], '', $path);
    if (! Storage::disk('public')->exists($path)) {
        abort(404);
    }
    $fullPath = Storage::disk('public')->path($path);
    if (! is_file($fullPath)) {
        abort(404);
    }
    $mime = @mime_content_type($fullPath);
    if (! is_string($mime) || $mime === '') {
        $mime = 'application/octet-stream';
    }
    return response()->file($fullPath, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.+');

Route::get('/', [RoomController::class, 'index'])->name('home');

Route::view('/lien-he', 'pages.contact')->name('pages.contact');
Route::view('/tro-giup', 'pages.help')->name('pages.help');
Route::view('/chinh-sach', 'pages.policy')->name('pages.policy');

Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
Route::get('/search', [RoomController::class, 'search'])->name('rooms.search');

Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
Route::get('/bookings/{booking}/cancel', [BookingCancellationController::class, 'show'])->name('bookings.cancel');
Route::post('/bookings/{booking}/cancel', [BookingCancellationController::class, 'cancel'])->name('bookings.cancel.post');
Route::get('/bookings/{booking}/policy', [BookingCancellationController::class, 'getPolicy'])->name('bookings.policy');
Route::get('/bookings/{booking}/refund', [BookingController::class, 'showRefundDetails'])->name('bookings.refund.details');
Route::post('/coupons/verify', [\App\Http\Controllers\CouponController::class, 'verify'])->name('coupons.verify');

Route::post('/rooms/{room}/reviews', [ReviewController::class, 'store'])->name('reviews.store')->middleware('auth');

Route::get('/payment/vnpay/pay/{booking}', [VnPayController::class, 'pay'])
    ->name('payment.vnpay.pay')
    ->middleware('signed');

Route::get('/payment/vnpay/return', [VnPayController::class, 'return'])->name('payment.vnpay.return');
Route::get('/payment/success/{booking}', [PaymentController::class, 'success'])->name('payment.success');
Route::get('/payment/failed', [PaymentController::class, 'failed'])->name('payment.failed');

Route::get('/admin/login', [AdminController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');

Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard/revenue-chart', [AdminController::class, 'revenueChartData'])->name('dashboard.revenue-chart');
    Route::get('/statistics/export', [AdminController::class, 'exportStatistics'])->name('statistics.export');
    Route::get('/rooms', [RoomAdminController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/create', [RoomAdminController::class, 'create'])->name('rooms.create');
    Route::post('/rooms', [RoomAdminController::class, 'store'])->name('rooms.store');
    Route::get('/rooms/{room}/edit', [RoomAdminController::class, 'edit'])->name('rooms.edit');
    Route::put('/rooms/{room}', [RoomAdminController::class, 'update'])->name('rooms.update');
    Route::delete('/rooms/{room}', [RoomAdminController::class, 'destroy'])->name('rooms.destroy');

    Route::get('/bookings', [BookingAdminController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/create', [BookingAdminController::class, 'create'])->name('bookings.create');
    Route::get('/bookings/create-multi', [BookingAdminController::class, 'createMulti'])->name('bookings.create-multi');
    Route::post('/bookings', [BookingAdminController::class, 'store'])->name('bookings.store');
    Route::post('/bookings/store-multi', [BookingAdminController::class, 'storeMulti'])->name('bookings.store-multi');
    Route::get('/bookings/check-availability', [BookingAdminController::class, 'checkAvailability'])->name('bookings.check-availability');
    Route::get('/bookings/validate-coupon', [BookingAdminController::class, 'validateCoupon'])->name('bookings.validate-coupon');
    Route::get('/bookings/{booking}/payment-instruction', [BookingAdminController::class, 'paymentInstruction'])->name('bookings.payment-instruction');
    Route::post('/bookings/{booking}/confirm-payment', [BookingAdminController::class, 'confirmPayment'])->name('bookings.confirm-payment');
    Route::get('/bookings/{booking}/invoice', [BookingAdminController::class, 'bookingInvoice'])->name('bookings.invoice');
    Route::get('/bookings/{booking}', [BookingAdminController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{booking}/status', [BookingAdminController::class, 'updateStatus'])->name('bookings.updateStatus');
    Route::post('/bookings/{booking}/payment-settings', [BookingAdminController::class, 'updatePaymentSettings'])->name('bookings.update-payment-settings');
    Route::post('/bookings/{booking}/checkin', [BookingAdminController::class, 'checkIn'])->name('bookings.checkIn');
    Route::get('/bookings/{booking}/guest-info', [BookingAdminController::class, 'getGuestInfo'])->name('bookings.guest-info');
    Route::put('/bookings/{booking}/guest-info', [BookingAdminController::class, 'updateGuestInfo'])->name('bookings.update-guest-info');
    Route::post('/bookings/{booking}/checkout', [BookingAdminController::class, 'checkOut'])->name('bookings.checkOut');
    Route::post('/bookings/{booking}/surcharge', [BookingAdminController::class, 'storeSurcharge'])->name('bookings.storeSurcharge');
    Route::post('/bookings/{booking}/booking-services', [BookingAdminController::class, 'storeBookingServices'])->name('bookings.storeBookingServices');
    Route::post('/bookings/{booking}/extras', [BookingAdminController::class, 'storeBookingExtras'])->name('bookings.storeExtras');
    Route::post('/bookings/{booking}/cancel', [BookingAdminController::class, 'cancel'])->name('bookings.cancel');

    Route::middleware(['admin_only'])->group(function () {
        Route::get('/bookings/{booking}/edit', [BookingAdminController::class, 'edit'])->name('bookings.edit');
        Route::put('/bookings/{booking}', [BookingAdminController::class, 'update'])->name('bookings.update');
        Route::delete('/bookings/{booking}', [BookingAdminController::class, 'destroy'])->name('bookings.destroy');
    });

    Route::get('/users', [UserAdminController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserAdminController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserAdminController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserAdminController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserAdminController::class, 'destroy'])->name('users.destroy');

    Route::get('/reviews', [ReviewAdminController::class, 'index'])->name('reviews.index');
    Route::get('/reviews/{review}', [ReviewAdminController::class, 'show'])->name('reviews.show');
    Route::post('/reviews/{review}/reply', [ReviewAdminController::class, 'reply'])->name('reviews.reply');
    Route::delete('/reviews/{review}', [ReviewAdminController::class, 'destroy'])->name('reviews.destroy');

    Route::get('/payments', [PaymentAdminController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}', [PaymentAdminController::class, 'show'])->name('payments.show');
    Route::get('/payments/{payment}/edit', [PaymentAdminController::class, 'edit'])->name('payments.edit');
    Route::put('/payments/{payment}', [PaymentAdminController::class, 'update'])->name('payments.update');
    Route::delete('/payments/{payment}', [PaymentAdminController::class, 'destroy'])->name('payments.destroy');

    Route::resource('coupons', CouponAdminController::class)->except(['show']);

    // ====== QUẢN LÝ HÓA ĐƠN ======
    Route::get('/invoices', [InvoiceAdminController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [InvoiceAdminController::class, 'show'])->name('invoices.show');
    Route::get('/bookings/{booking}/invoices/create', [InvoiceAdminController::class, 'create'])->name('invoices.create');
    Route::post('/bookings/{booking}/invoices', [InvoiceAdminController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}/edit', [InvoiceAdminController::class, 'edit'])->name('invoices.edit');
    Route::put('/invoices/{invoice}', [InvoiceAdminController::class, 'update'])->name('invoices.update');
    Route::delete('/invoices/{invoice}', [InvoiceAdminController::class, 'destroy'])->name('invoices.destroy');
    Route::post('/invoices/{invoice}/paid', [InvoiceAdminController::class, 'markAsPaid'])->name('invoices.markAsPaid');
    Route::post('/invoices/{invoice}/sync-booking-extras', [InvoiceAdminController::class, 'syncBookingExtras'])->name('invoices.sync-booking-extras');
    Route::get('/invoices/{invoice}/print', [InvoiceAdminController::class, 'print'])->name('invoices.print');

    Route::get('/settings', [SettingsAdminController::class, 'index'])->name('settings.index');
    Route::put('/settings/general', [SettingsAdminController::class, 'updateGeneral'])->name('settings.update.general');
    Route::put('/settings/site-content', [SettingsAdminController::class, 'updateSiteContent'])->name('settings.update.site.content');

    // Refund Management
    Route::get('/refunds', [RefundAdminController::class, 'index'])->name('refunds.index');
    Route::get('/refunds/{refundRequest}', [RefundAdminController::class, 'show'])->name('refunds.show');
    Route::post('/refunds/{refundRequest}/process', [RefundAdminController::class, 'process'])->name('refunds.process');

    // Damage Reports Management
    Route::get('/damage-reports', [\App\Http\Controllers\Admin\DamageReportController::class, 'index'])->name('damage-reports.index');
    Route::get('/damage-reports/create', [\App\Http\Controllers\Admin\DamageReportController::class, 'create'])->name('damage-reports.create');
    Route::post('/damage-reports', [\App\Http\Controllers\Admin\DamageReportController::class, 'store'])->name('damage-reports.store');
    Route::get('/damage-reports/{damageReport}', [\App\Http\Controllers\Admin\DamageReportController::class, 'show'])->name('damage-reports.show');
    Route::post('/damage-reports/{damageReport}/status', [\App\Http\Controllers\Admin\DamageReportController::class, 'updateStatus'])->name('damage-reports.update-status');
    Route::post('/damage-reports/{damageReport}/change-room', [\App\Http\Controllers\Admin\DamageReportController::class, 'changeRoom'])->name('damage-reports.change-room');
    Route::post('/damage-reports/{damageReport}/refund', [\App\Http\Controllers\Admin\DamageReportController::class, 'processRefund'])->name('damage-reports.process-refund');

    // ====== QUẢN LÝ DỊCH VỤ ======
    Route::prefix('services')->name('services.')->group(function () {
        Route::get('/', [ServiceController::class, 'index'])->name('index');
        Route::get('/create', [ServiceController::class, 'create'])->name('create');
        Route::post('/', [ServiceController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [ServiceController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ServiceController::class, 'update'])->name('update');
        Route::delete('/{id}', [ServiceController::class, 'destroy'])->name('destroy');
    });

    // ====== QUẢN LÝ LOẠI PHÒNG ======
    Route::prefix('roomtypes')->name('roomtypes.')->group(function () {
        Route::get('/', [RoomTypeController::class, 'index'])->name('index');
        Route::get('/create', [RoomTypeController::class, 'create'])->name('create');
        Route::post('/', [RoomTypeController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [RoomTypeController::class, 'edit'])->name('edit');
        Route::put('/{id}', [RoomTypeController::class, 'update'])->name('update');
        Route::delete('/{id}', [RoomTypeController::class, 'destroy'])->name('destroy');
    });



});

// Check-in Routes
Route::middleware('auth')->prefix('checkin')->name('checkin.')->group(function () {
    Route::get('/bookings/{booking}', [GuestCheckInController::class, 'index'])->name('index');
    Route::post('/guests/{guest}/status', [GuestCheckInController::class, 'updateGuestStatus'])->name('guest.status');
    Route::post('/bookings/{booking}/checkin-all', [GuestCheckInController::class, 'checkInAll'])->name('all');
    Route::get('/bookings/{booking}/guests', [GuestCheckInController::class, 'getGuestList'])->name('guests');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/bookings', [AccountController::class, 'bookings'])->name('bookings');
    Route::get('/bookings/{booking}/invoice', [AccountController::class, 'bookingInvoice'])->name('bookings.invoice');
    Route::get('/bookings/{booking}', [AccountController::class, 'showBooking'])->name('bookings.show');
    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
    Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AccountController::class, 'updatePassword'])->name('profile.update.password');

    // Refund routes
    Route::get('/bookings/{booking}/refund', [AccountController::class, 'refundForm'])->name('bookings.refund');
    Route::post('/bookings/{booking}/refund', [AccountController::class, 'submitRefund'])->name('bookings.refund.submit');
});

use App\Http\Controllers\Staff\StaffController;

Route::middleware(['auth', 'staff'])
    ->prefix('staff')
    ->group(function () {
        Route::get('/dashboard', [StaffController::class, 'dashboard']);
    });


