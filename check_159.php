<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$b = DB::table('bookings')->where('id', 159)->first();
echo "Booking #159:\n";
echo "  adults (from bookings): {$b->adults}\n";
echo "  guests (from bookings): {$b->guests}\n";

$bgAdults = DB::table('booking_guests')->where('booking_id', 159)->where('type', 'adult')->count();
$bgChildren = DB::table('booking_guests')->where('booking_id', 159)->where('type', 'child')->count();
echo "\nbooking_guests:\n";
echo "  adults: $bgAdults\n";
echo "  children: $bgChildren\n";

// Xóa booking_guests để hiển thị đúng số phòng
if ($bgAdults > 0) {
    DB::table('booking_guests')->where('booking_id', 159)->delete();
    echo "\nDeleted booking_guests for #159\n";
}
