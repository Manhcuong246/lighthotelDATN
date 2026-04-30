<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CheckBooking154Rooms extends Seeder
{
    public function run(): void
    {
        echo "=== Booking #154 Rooms Check ===\n\n";
        
        $bookingRooms = DB::table('booking_rooms')
            ->where('booking_id', 154)
            ->join('rooms', 'booking_rooms.room_id', '=', 'rooms.id')
            ->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
            ->select('booking_rooms.*', 'rooms.room_number', 'room_types.name as room_type_name')
            ->get();
        
        echo "Total booking rooms: " . $bookingRooms->count() . "\n\n";
        
        $deluxeCount = 0;
        foreach ($bookingRooms as $index => $br) {
            echo ($index + 1) . ". Room ID: {$br->room_id}, Room Number: {$br->room_number}, Type: {$br->room_type_name}\n";
            if (stripos($br->room_type_name, 'deluxe') !== false) {
                $deluxeCount++;
            }
        }
        
        echo "\nDeluxe rooms count: {$deluxeCount}\n";
    }
}
