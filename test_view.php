<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $booking = \App\Models\Booking::find(154);
    $v = view('bookings.admin-show', ['booking' => $booking])->render();
    echo "admin-show OK\n";
} catch (Exception $e) {
    echo "admin-show ERROR: " . $e->getMessage() . "\n";
}

try {
    $v = view('admin.bookings._checkin_modal', ['booking' => $booking])->render();
    echo "_checkin_modal OK\n";
} catch (Exception $e) {
    echo "_checkin_modal ERROR: " . $e->getMessage() . "\n";
}
