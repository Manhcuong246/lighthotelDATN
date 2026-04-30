<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CheckCccd149 extends Seeder
{
    public function run(): void
    {
        $bookingId = 149;
        
        echo "=== CCCD for Booking #$bookingId ===\n\n";
        
        $bookingGuests = DB::table('booking_guests')->where('booking_id', $bookingId)->get();
        echo "booking_guests:\n";
        foreach ($bookingGuests as $bg) {
            echo "  - {$bg->name}: CCCD = " . ($bg->cccd ?? 'NULL') . "\n";
        }
        
        $legacyGuests = DB::table('guests')->where('booking_id', $bookingId)->get();
        echo "\nlegacy guests:\n";
        foreach ($legacyGuests as $g) {
            echo "  - {$g->name}: CCCD = " . ($g->cccd ?? 'NULL') . "\n";
        }
    }
}
