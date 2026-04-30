<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CheckCccd153 extends Seeder
{
    public function run(): void
    {
        $bookingId = 153;
        
        echo "=== Checking CCCD for Booking #$bookingId ===\n\n";
        
        // Check booking
        $booking = DB::table('bookings')->where('id', $bookingId)->first();
        echo "Booking.cccd: " . ($booking->cccd ?? 'NULL') . "\n";
        echo "Booking.user_id: " . ($booking->user_id ?? 'NULL') . "\n\n";
        
        // Check user
        if ($booking->user_id) {
            $user = DB::table('users')->where('id', $booking->user_id)->first();
            if ($user) {
                echo "User.cccd: " . ($user->cccd ?? 'NULL') . "\n";
                echo "User.identity_card: " . ($user->identity_card ?? 'NULL') . "\n";
                echo "User.cmnd: " . ($user->cmnd ?? 'NULL') . "\n\n";
            }
        }
        
        // Check legacy guests
        $guests = DB::table('guests')->where('booking_id', $bookingId)->get();
        echo "Legacy guests count: " . $guests->count() . "\n";
        foreach ($guests as $g) {
            echo "  Guest: {$g->name}, CCCD: " . ($g->cccd ?? 'NULL') . ", Rep: {$g->is_representative}\n";
        }
        
        // Check booking_guests
        $bg = DB::table('booking_guests')->where('booking_id', $bookingId)->get();
        echo "\nBooking guests count: " . $bg->count() . "\n";
        foreach ($bg as $g) {
            echo "  Guest: {$g->name}, CCCD: " . ($g->cccd ?? 'NULL') . ", Rep: {$g->is_representative}\n";
        }
    }
}
