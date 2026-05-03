<?php

namespace App\Providers;

use App\Models\HotelInfo;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        $this->app['router']->aliasMiddleware('admin.only', \App\Http\Middleware\AdminOnlyMiddleware::class);

        View::composer(
            ['layouts.app', 'pages.*'],
            function ($view) {
                $view->with('hotelInfo', HotelInfo::first());
            }
        );
    }
}
