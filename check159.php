<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$b = DB::table('bookings')->where('id', 159)->first();
if (!$b) {
    echo "Booking #159 not found\n";
    exit;
}

echo "Booking #159:\n";
echo "  adults: {$b->adults}\n";
echo "  guests: {$b->guests}\n";
echo "  quantity: {$b->quantity}\n";

// Sửa nếu cần
if ($b->adults != 3) {
    DB::table('bookings')->where('id', 159)->update([
        'adults' => 3,
        'guests' => 3,
    ]);
    echo "\nUpdated to 3 adults\n";
} else {
    echo "\nAlready correct (3 adults)\n";
}
