<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$bookings = Illuminate\Support\Facades\Schema::getColumnListing('bookings');
$roomTypes = Illuminate\Support\Facades\Schema::getColumnListing('room_types');

file_put_contents('check_db.json', json_encode([
    'bookings' => $bookings,
    'room_types' => $roomTypes
], JSON_PRETTY_PRINT));
