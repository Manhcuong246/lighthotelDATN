<?php
require __DIR__ . '/../vendor/autoload.php';
// Load Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Booking;
use App\Services\RefundService;
use Carbon\Carbon;

$booking = Booking::find(63);
echo "Booking 63 Status: " . $booking->status . "\n";
echo "Booking 63 Check-in: " . $booking->check_in . "\n";

$refundService = new RefundService();
$calc = $refundService->calculateRefund($booking);

echo "Calculation result:\n";
print_r($calc);

echo "\nNow: " . Carbon::now()->toDateTimeString() . "\n";
echo "Check-in time considered: " . Carbon::parse($booking->check_in)->setTime(14, 0, 0)->toDateTimeString() . "\n";
echo "Hours difference: " . Carbon::now()->diffInHours(Carbon::parse($booking->check_in)->setTime(14, 0, 0), false) . "\n";
