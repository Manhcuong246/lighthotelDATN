<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Booking;
use Carbon\Carbon;

$booking = Booking::with('payment')->find(64);

echo "App Timezone: " . config('app.timezone') . "\n";
echo "Now: " . now() . "\n";
echo "Booking Created At (Raw DB): " . ($booking->getAttributes()['created_at'] ?? 'N/A') . "\n";
echo "Booking Created At (Eloquent): " . $booking->created_at . "\n";
if ($booking->payment) {
    echo "Payment Paid At (Raw DB): " . ($booking->payment->getAttributes()['paid_at'] ?? 'N/A') . "\n";
    echo "Payment Paid At (Eloquent): " . $booking->payment->paid_at . "\n";
}
