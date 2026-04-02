<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "App Timezone: " . config('app.timezone') . "<br>";
echo "Now: " . now() . "<br>";
echo "Server Time: " . date('Y-m-d H:i:s') . " " . date_default_timezone_get() . "<br><hr>";

$booking = \App\Models\Booking::with('payment')->find(64);
if ($booking) {
    echo "Booking ID: " . $booking->id . "<br>";
    echo "Created At (Eloquent): " . $booking->created_at . " (Timezone: " . $booking->created_at->timezoneName . ")<br>";
    echo "Created At (Raw DB): " . $booking->getAttributes()['created_at'] . "<br>";
    if ($booking->payment) {
        echo "Paid At (Eloquent): " . $booking->payment->paid_at . " (Timezone: " . $booking->payment->paid_at->timezoneName . ")<br>";
        echo "Paid At (Raw DB): " . $booking->payment->getAttributes()['paid_at'] . "<br>";
    }
} else {
    echo "No booking #64 found.";
}

