<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DebugBooking150Detail extends Seeder
{
    public function run(): void
    {
        $bookingId = 150;
        
        echo "=== Booking #$bookingId Booking Guests Detail ===\n\n";
        
        $bgGuests = DB::table('booking_guests')
            ->where('booking_id', $bookingId)
            ->select('id', 'booking_id', 'booking_room_id', 'name', 'cccd', 'is_representative', 'status')
            ->get();
        
        echo "booking_guests:\n";
        foreach ($bgGuests as $g) {
            echo "  ID: {$g->id}, Name: {$g->name}, booking_room_id: {$g->booking_room_id}, rep: {$g->is_representative}\n";
        }
        
        echo "\n=== Booking Rooms ===\n";
        $bRooms = DB::table('booking_rooms')->where('booking_id', $bookingId)->get();
        foreach ($bRooms as $br) {
            echo "  ID: {$br->id}, Room ID: {$br->room_id}\n";
        }
    }
}
