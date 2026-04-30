<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $booking = \App\Models\Booking::with(['bookingGuests', 'bookingRooms.room', 'user', 'rooms'])->find(154);
    $html = view('bookings.admin-show', ['booking' => $booking])->render();
    echo "OK - " . strlen($html) . " chars\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
