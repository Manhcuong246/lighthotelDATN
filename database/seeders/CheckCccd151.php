<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CheckCccd151 extends Seeder
{
    public function run(): void
    {
        $bookingId = 151;
        
        echo "=== Booking #$bookingId CCCD Check ===\n\n";
        
        // Check booking.cccd
        $booking = DB::table('bookings')->where('id', $bookingId)->first();
        echo "booking.cccd: " . ($booking->cccd ?? 'NULL') . "\n";
        
        // Check booking_guests
        echo "\nbooking_guests:\n";
        $bg = DB::table('booking_guests')->where('booking_id', $bookingId)->get();
        foreach ($bg as $g) {
            echo "  - {$g->name}: CCCD=" . ($g->cccd ?? 'NULL') . "\n";
        }
        
        // Check guests (legacy)
        echo "\nguests (legacy):\n";
        $lg = DB::table('guests')->where('booking_id', $bookingId)->get();
        foreach ($lg as $g) {
            echo "  - {$g->name}: CCCD=" . ($g->cccd ?? 'NULL') . "\n";
        }
    }
}
