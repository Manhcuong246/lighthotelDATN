<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Kiểm tra các phòng của booking 154
$bookingRooms = DB::table('booking_rooms')->where('booking_id', 154)->get();
echo "Booking rooms for #154:\n";
foreach ($bookingRooms as $br) {
    $room = DB::table('rooms')->where('id', $br->room_id)->first();
    $roomType = DB::table('room_types')->where('id', $room->room_type_id ?? null)->first();
    echo "  br_id: {$br->id}, room_id: {$br->room_id}, room_number: {$room->room_number}, type: {$roomType->name}\n";
}

// Kiểm tra xem có phòng Deluxe nào khác không
echo "\nAll Deluxe rooms:\n";
$deluxeType = DB::table('room_types')->where('name', 'like', '%Deluxe%')->first();
if ($deluxeType) {
    $deluxeRooms = DB::table('rooms')->where('room_type_id', $deluxeType->id)->get();
    foreach ($deluxeRooms as $r) {
        echo "  Room {$r->room_number} (ID: {$r->id})\n";
    }
}
