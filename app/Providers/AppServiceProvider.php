<?php

namespace App\Providers;

use App\Models\HotelInfo;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        $this->app['router']->aliasMiddleware('admin', \App\Http\Middleware\AdminMiddleware::class);
        $this->app['router']->aliasMiddleware('admin.only', \App\Http\Middleware\AdminOnlyMiddleware::class);

        View::composer(
            ['layouts.app', 'pages.*'],
            function ($view) {
                $view->with('hotelInfo', once(fn () => HotelInfo::first()));
            }
        );
    }
}
