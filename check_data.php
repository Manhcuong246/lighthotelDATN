<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$bookingId = 154;

echo "Booking guests:\n";
$guests = DB::table('booking_guests')->where('booking_id', $bookingId)->get();
foreach ($guests as $g) {
    echo "  {$g->name} - rep:{$g->is_representative} - type:{$g->type} - br_id:{$g->booking_room_id}\n";
}
