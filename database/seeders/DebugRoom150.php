<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DebugRoom150 extends Seeder
{
    public function run(): void
    {
        $bookingId = 150;
        
        $rooms = DB::table('rooms')
            ->join('booking_rooms', 'rooms.id', '=', 'booking_rooms.room_id')
            ->where('booking_rooms.booking_id', $bookingId)
            ->select('rooms.id', 'rooms.name', 'rooms.room_number', 'rooms.room_type_id')
            ->get();
        
        echo "=== Room Data ===\n";
        foreach ($rooms as $room) {
            echo "ID: {$room->id}\n";
            echo "Name: '{$room->name}'\n";
            echo "Room Number: '{$room->room_number}'\n";
            echo "Room Type ID: {$room->room_type_id}\n";
        }
        
        // Get room type name
        $roomTypeId = $rooms->first()?->room_type_id;
        if ($roomTypeId) {
            $type = DB::table('room_types')->where('id', $roomTypeId)->first();
            echo "Room Type Name: '{$type->name}'\n";
        }
    }
}
