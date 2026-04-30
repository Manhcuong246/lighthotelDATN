<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test API getCheckInData
$controller = new \App\Http\Controllers\Admin\BookingAdminController();
$booking = \App\Models\Booking::find(159);

if (!$booking) {
    echo "Booking #159 not found\n";
    exit;
}

try {
    $response = $controller->getCheckInData($booking);
    $data = $response->getData(true);
    echo "API Response:\n";
    echo "  success: " . ($data['success'] ?? 'null') . "\n";
    echo "  guests count: " . count($data['guests'] ?? []) . "\n";
    echo "  booking_rooms count: " . count($data['booking_rooms'] ?? []) . "\n";
    if (isset($data['error'])) {
        echo "  error: " . $data['error'] . "\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
