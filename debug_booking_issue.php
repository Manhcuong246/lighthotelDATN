<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DEBUG BOOKING ISSUE ===" . PHP_EOL;

// Kiểm tra database structure
echo "1. Database structure:" . PHP_EOL;
echo " - Bookings table: " . (\Schema::hasTable('bookings') ? 'EXISTS' : 'NOT EXISTS') . PHP_EOL;
echo " - New Bookings table: " . (\Schema::hasTable('new_bookings') ? 'EXISTS' : 'NOT EXISTS') . PHP_EOL;
echo " - Guests table: " . (\Schema::hasTable('guests') ? 'EXISTS' : 'NOT EXISTS') . PHP_EOL;
echo " - Booking Guests table: " . (\Schema::hasTable('booking_guests') ? 'EXISTS' : 'NOT EXISTS') . PHP_EOL;

// Kiểm tra data
echo PHP_EOL . "2. Data check:" . PHP_EOL;
echo " - New Bookings count: " . \App\Models\NewBooking::count() . PHP_EOL;
echo " - Guests count: " . \App\Models\Guest::count() . PHP_EOL;

// Kiểm tra recent bookings
echo PHP_EOL . "3. Recent bookings:" . PHP_EOL;
$recentBookings = \App\Models\NewBooking::orderBy('created_at', 'desc')->limit(5)->get();
foreach ($recentBookings as $booking) {
    echo "   Booking ID: {$booking->id}" . PHP_EOL;
    echo "   - Status: {$booking->status}" . PHP_EOL;
    echo "   - Guests count: " . $booking->guests->count() . PHP_EOL;
    echo "   - Created: {$booking->created_at}" . PHP_EOL;
    echo PHP_EOL;
}

// Kiểm tra routes
echo PHP_EOL . "4. Routes check:" . PHP_EOL;
$routes = \Route::getRoutes();
foreach ($routes as $route) {
    if (strpos($route->uri, 'bookings') !== false) {
        echo "   - {$route->methods[0]} {$route->uri} -> {$route->action}" . PHP_EOL;
    }
}

echo PHP_EOL . "=== END DEBUG ===" . PHP_EOL;
