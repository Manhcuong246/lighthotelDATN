<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$bookingId = 154;

// Lấy thông tin booking rooms
$bookingRooms = DB::table('booking_rooms')->where('booking_id', $bookingId)->get();
echo "Booking rooms:\n";
foreach ($bookingRooms as $br) {
    $room = DB::table('rooms')->where('id', $br->room_id)->first();
    $roomType = DB::table('room_types')->where('id', $room->room_type_id ?? null)->first();
    echo "  br_id: {$br->id}, room_id: {$br->room_id}, room_number: {$room->room_number}, type: {$roomType->name}\n";
}

// Lấy người đại diện đã có
$repGuest = DB::table('booking_guests')->where('booking_id', $bookingId)->where('is_representative', 1)->first();
echo "\nRep guest exists: " . ($repGuest ? 'Yes - ' . $repGuest->name : 'No') . "\n";
