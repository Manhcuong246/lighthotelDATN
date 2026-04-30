<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CheckNewestBooking extends Seeder
{
    public function run(): void
    {
        $booking = DB::table('bookings')->orderBy('id', 'desc')->first();
        
        if (!$booking) {
            echo "No bookings found\n";
            return;
        }
        
        $bookingId = $booking->id;
        echo "=== Newest Booking: #$bookingId ===\n\n";
        
        $bookingGuests = DB::table('booking_guests')->where('booking_id', $bookingId)->get();
        echo "booking_guests: " . $bookingGuests->count() . "\n";
        foreach ($bookingGuests as $bg) {
            echo "  - {$bg->name} (rep: {$bg->is_representative})\n";
        }
        
        $legacyGuests = DB::table('guests')->where('booking_id', $bookingId)->get();
        echo "\nlegacy guests: " . $legacyGuests->count() . "\n";
        foreach ($legacyGuests as $g) {
            echo "  - {$g->name} (rep: {$g->is_representative})\n";
        }
        
        $bookingRooms = DB::table('booking_rooms')->where('booking_id', $bookingId)->get();
        echo "\nbooking_rooms: " . $bookingRooms->count() . "\n";
        foreach ($bookingRooms as $br) {
            $room = DB::table('rooms')->where('id', $br->room_id)->first();
            echo "  - {$room->name}, adults: {$br->adults}, total: " . ($br->adults + $br->children_0_5 + $br->children_6_11) . "\n";
        }
    }
}
