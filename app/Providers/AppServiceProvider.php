<?php

namespace App\Providers;

use App\Models\HotelInfo;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $root = config('app.url');
        if (is_string($root) && $root !== '') {
            // Tránh lệch chữ ký URL (signed / temporarySigned) khi generate vs request thực tế
            URL::forceRootUrl(rtrim($root, '/'));
        }
        if (is_string($root) && str_starts_with($root, 'https://')) {
            URL::forceScheme('https');
        }

        Paginator::useBootstrapFive();
        $this->app['router']->aliasMiddleware('admin', \App\Http\Middleware\AdminMiddleware::class);

        RateLimiter::for('coupon-verify', function (Request $request): Limit {
            return Limit::perMinute(30)->by((string) $request->ip());
        });

        View::composer(
            ['layouts.app', 'pages.*', 'layouts.admin'],
            function ($view) {
                $view->with('hotelInfo', HotelInfo::first());
            }
        );
    }
}
