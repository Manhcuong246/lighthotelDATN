<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DebugBooking149 extends Seeder
{
    public function run(): void
    {
        $bookingId = 149;
        
        echo "=== DEBUG Booking #$bookingId ===\n\n";
        
        // 1. Check booking guests
        $bookingGuests = DB::table('booking_guests')->where('booking_id', $bookingId)->get();
        echo "booking_guests table:\n";
        foreach ($bookingGuests as $bg) {
            echo "  ID: {$bg->id}, Name: {$bg->name}, Rep: {$bg->is_representative}, Type: {$bg->type}, BookingRoomID: {$bg->booking_room_id}\n";
        }
        echo "  Total: " . $bookingGuests->count() . "\n\n";
        
        // 2. Check legacy guests
        $legacyGuests = DB::table('guests')->where('booking_id', $bookingId)->get();
        echo "guests (legacy) table:\n";
        foreach ($legacyGuests as $g) {
            echo "  ID: {$g->id}, Name: {$g->name}, Rep: {$g->is_representative}, Type: {$g->type}, RoomID: {$g->room_id}\n";
        }
        echo "  Total: " . $legacyGuests->count() . "\n\n";
        
        // 3. Check booking rooms
        $bookingRooms = DB::table('booking_rooms')->where('booking_id', $bookingId)->get();
        echo "booking_rooms:\n";
        foreach ($bookingRooms as $br) {
            $room = DB::table('rooms')->where('id', $br->room_id)->first();
            echo "  ID: {$br->id}, Room: {$room->name}, Adults: {$br->adults}, Child0-5: {$br->children_0_5}, Child6-11: {$br->children_6_11}\n";
        }
        echo "  Total rooms: " . $bookingRooms->count() . "\n\n";
        
        // 4. Summary
        $totalGuestsExpected = $bookingRooms->sum('adults') + $bookingRooms->sum('children_0_5') + $bookingRooms->sum('children_6_11');
        echo "Expected guests: {$totalGuestsExpected}\n";
        echo "Legacy guests: " . $legacyGuests->count() . "\n";
        echo "Booking guests: " . $bookingGuests->count() . "\n";
        
        if ($bookingGuests->count() < $legacyGuests->count()) {
            echo "\n❌ ERROR: booking_guests has fewer records than guests!\n";
        }
    }
}
