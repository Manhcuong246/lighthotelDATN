<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$booking = \App\Models\Booking::with(['bookingGuests', 'bookingRooms.room'])->find(154);

// Build guestsByRoom data
$guestsByRoom = [];
foreach ($booking->bookingGuests as $guest) {
    $brId = $guest->booking_room_id;
    if (!isset($guestsByRoom[$brId])) {
        $guestsByRoom[$brId] = [
            'room' => $brId ? $booking->bookingRooms->firstWhere('id', $brId)?->room : null,
            'guests' => collect()
        ];
    }
    $guestsByRoom[$brId]['guests']->push($guest);
}

try {
    $html = view('admin.bookings._guests_by_room', [
        'booking' => $booking,
        'guestsByRoom' => $guestsByRoom
    ])->render();
    echo "OK - Length: " . strlen($html) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
