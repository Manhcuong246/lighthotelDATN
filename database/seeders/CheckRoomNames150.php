<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CheckRoomNames150 extends Seeder
{
    public function run(): void
    {
        $bookingId = 150;
        
        echo "=== Rooms for Booking #$bookingId ===\n";
        
        $bookingRooms = DB::table('booking_rooms')
            ->join('rooms', 'booking_rooms.room_id', '=', 'rooms.id')
            ->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
            ->where('booking_rooms.booking_id', $bookingId)
            ->select('rooms.name as room_name', 'room_types.name as type_name', 'rooms.room_number')
            ->get();
        
        foreach ($bookingRooms as $br) {
            echo "Room: {$br->room_name}, Type: {$br->type_name}, Number: {$br->room_number}\n";
        }
    }
}
