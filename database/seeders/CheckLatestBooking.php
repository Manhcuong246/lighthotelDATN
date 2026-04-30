<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CheckLatestBooking extends Seeder
{
    public function run(): void
    {
        // Tìm booking mới nhất có Standard 103
        $booking = DB::table('bookings')
            ->join('booking_rooms', 'bookings.id', '=', 'booking_rooms.booking_id')
            ->join('rooms', 'booking_rooms.room_id', '=', 'rooms.id')
            ->where('rooms.name', 'like', '%103%')
            ->orderBy('bookings.id', 'desc')
            ->select('bookings.*')
            ->first();
        
        if (!$booking) {
            echo "No booking found with room 103\n";
            return;
        }
        
        $bookingId = $booking->id;
        echo "=== Latest Booking with 103: #$bookingId ===\n";
        
        $bookingGuests = DB::table('booking_guests')->where('booking_id', $bookingId)->get();
        echo "BookingGuests count: " . $bookingGuests->count() . "\n";
        foreach ($bookingGuests as $bg) {
            echo "  - {$bg->name} (rep: {$bg->is_representative})\n";
        }
        
        $legacyGuests = DB::table('guests')->where('booking_id', $bookingId)->get();
        echo "\nLegacy Guests count: " . $legacyGuests->count() . "\n";
        foreach ($legacyGuests as $g) {
            echo "  - {$g->name} (rep: {$g->is_representative})\n";
        }
        
        $bookingRooms = DB::table('booking_rooms')->where('booking_id', $bookingId)->get();
        echo "\nBookingRooms:\n";
        foreach ($bookingRooms as $br) {
            $room = DB::table('rooms')->where('id', $br->room_id)->first();
            echo "  - {$room->name}, adults: {$br->adults}, children: " . ($br->children_0_5 + $br->children_6_11) . "\n";
        }
    }
}
