<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Env;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $trusted = Env::get('TRUSTED_PROXIES');
        if (filled($trusted)) {
            $at = $trusted === '*'
                ? '*'
                : array_values(array_filter(array_map('trim', explode(',', $trusted))));
            $middleware->trustProxies(at: $at);
        }

        // ==============================
        // Middleware Alias
        // ==============================

        $middleware->web(append: [
            \App\Http\Middleware\EnsureUserAccountAllowed::class,
        ]);

        $middleware->alias([

            // Admin
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'admin_only' => \App\Http\Middleware\AdminOnlyMiddleware::class,
            'admin.only' => \App\Http\Middleware\AdminOnlyMiddleware::class,

            // Staff (THÊM MỚI)
            'staff' => \App\Http\Middleware\StaffMiddleware::class,

            // Chỉ role admin (không gồm staff)
            'only_admin' => \App\Http\Middleware\EnsureUserIsAdministrator::class,

            // Default Laravel
            'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,

        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (InvalidSignatureException $e, Request $request) {
            if ($request->is('payment/*', 'guest/*', 'bookings/*')) {
                return redirect()
                    ->route('home')
                    ->withErrors(
                        'Liên kết không hợp lệ hoặc đã hết hạn. Hãy đảm bảo APP_URL trong .env đúng URL bạn đang mở (vd. ngrok https), chạy php artisan config:clear, và TRUSTED_PROXIES=* nếu có HTTPS phía proxy.'
                    );
            }

            return null;
        });
    })
    ->create();