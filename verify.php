<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$bookingId = 154;

echo "=== BOOKING ROOMS ===\n";
$rooms = DB::table('booking_rooms')->where('booking_id', $bookingId)->get();
foreach ($rooms as $r) {
    echo "br_id: {$r->id}, room_id: {$r->room_id}\n";
}

echo "\n=== BOOKING GUESTS ===\n";
$guests = DB::table('booking_guests')->where('booking_id', $bookingId)->get();
foreach ($guests as $g) {
    echo "{$g->name} - rep:{$g->is_representative} - br_id:{$g->booking_room_id} - type:{$g->type}\n";
}

echo "\nTotal: " . $guests->count() . " guests\n";
