<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Tìm booking có adults = 2
$bookings = DB::table('bookings')->where('adults', '>=', 2)->orderBy('id', 'desc')->limit(5)->get();

echo "Bookings with adults >= 2:\n";
foreach ($bookings as $b) {
    echo "  Booking #{$b->id}: adults={$b->adults}, children={$b->children}, guests={$b->guests}\n";
}

// Liệt kê 5 booking mới nhất
$latest = DB::table('bookings')->orderBy('id', 'desc')->limit(5)->get();
echo "\n5 latest bookings:\n";
foreach ($latest as $b) {
    echo "  Booking #{$b->id}: adults={$b->adults}, children={$b->children}\n";
}
