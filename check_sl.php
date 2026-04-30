<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Lấy booking mới nhất hoặc theo ID
$bookingId = $argv[1] ?? 158;

$booking = DB::table('bookings')->where('id', $bookingId)->first();
if (!$booking) {
    echo "Booking #$bookingId not found\n";
    exit;
}

echo "Booking #$bookingId:\n";
echo "  adults (from booking): {$booking->adults}\n";
echo "  children (from booking): {$booking->children}\n";
echo "  guests (from booking): {$booking->guests}\n";
echo "  quantity (rooms): {$booking->quantity}\n";

// Đếm bookingGuests
$bgAdults = DB::table('booking_guests')->where('booking_id', $bookingId)->where('type', 'adult')->count();
$bgChildren = DB::table('booking_guests')->where('booking_id', $bookingId)->where('type', 'child')->count();
echo "\nbooking_guests:\n";
echo "  adults: $bgAdults\n";
echo "  children: $bgChildren\n";
