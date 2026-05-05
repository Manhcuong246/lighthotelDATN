<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| PHPUnit bootstrap — ép DB test trước khi Laravel đọc env hệ thống.
|--------------------------------------------------------------------------
|
| Trên một số máy Windows, biến môi trường toàn cục DB_CONNECTION=sqlite /
| DB_DATABASE=:memory: ghi đè phpunit.xml — migrations MySQL-only sẽ lỗi.
|
*/
foreach ([
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'mysql',
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3307',
    'DB_DATABASE' => 'hotel_booking_test',
    'DB_USERNAME' => 'app',
    'DB_PASSWORD' => 'app_password',
] as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require dirname(__DIR__).'/vendor/autoload.php';
