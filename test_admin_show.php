<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $booking = \App\Models\Booking::with(['bookingGuests', 'bookingRooms.room', 'user', 'rooms'])->find(154);
    $html = view('bookings.admin-show', ['booking' => $booking])->render();
    echo "admin-show OK\n";
    echo "Length: " . strlen($html) . " chars\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}
