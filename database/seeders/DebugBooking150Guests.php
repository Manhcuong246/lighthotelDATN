<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DebugBooking150Guests extends Seeder
{
    public function run(): void
    {
        $bookingId = 150;
        
        echo "=== Booking #$bookingId Guests ===\n\n";
        
        // Check booking_guests
        $bgGuests = DB::table('booking_guests')->where('booking_id', $bookingId)->get();
        echo "booking_guests count: " . $bgGuests->count() . "\n";
        foreach ($bgGuests as $g) {
            echo "  - {$g->name} (rep={$g->is_representative}, status={$g->status})\n";
        }
        
        echo "\n";
        
        // Check legacy guests
        $lgGuests = DB::table('guests')->where('booking_id', $bookingId)->get();
        echo "guests (legacy) count: " . $lgGuests->count() . "\n";
        foreach ($lgGuests as $g) {
            echo "  - {$g->name} (rep={$g->is_representative}, status={$g->checkin_status})\n";
        }
    }
}
