<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CheckBooking147 extends Seeder
{
    public function run(): void
    {
        $bookingId = 147;
        
        echo "=== Booking #$bookingId ===\n";
        
        $booking = DB::table('bookings')->where('id', $bookingId)->first();
        if (!$booking) {
            echo "Booking not found\n";
            return;
        }
        
        echo "Status: {$booking->status}\n";
        echo "Guests count (booking.guests): {$booking->guests}\n";
        
        $bookingGuests = DB::table('booking_guests')->where('booking_id', $bookingId)->get();
        echo "\nBookingGuests count: " . $bookingGuests->count() . "\n";
        foreach ($bookingGuests as $bg) {
            echo "  - {$bg->name} (rep: {$bg->is_representative}, type: {$bg->type})\n";
        }
        
        $legacyGuests = DB::table('guests')->where('booking_id', $bookingId)->get();
        echo "\nLegacy Guests count: " . $legacyGuests->count() . "\n";
        foreach ($legacyGuests as $g) {
            echo "  - {$g->name} (rep: {$g->is_representative}, type: {$g->type})\n";
        }
        
        $bookingRooms = DB::table('booking_rooms')->where('booking_id', $bookingId)->get();
        echo "\nBookingRooms count: " . $bookingRooms->count() . "\n";
        foreach ($bookingRooms as $br) {
            echo "  - Room ID: {$br->room_id}, adults: {$br->adults}, children: " . ($br->children_0_5 + $br->children_6_11) . "\n";
        }
    }
}
