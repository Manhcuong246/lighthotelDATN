<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CheckLatest extends Seeder
{
    public function run(): void
    {
        $booking = DB::table('bookings')->orderBy('id', 'desc')->first();
        if (!$booking) return;
        
        $bookingId = $booking->id;
        echo "=== Booking #$bookingId ===\n";
        
        $bg = DB::table('booking_guests')->where('booking_id', $bookingId)->get();
        echo "booking_guests: " . $bg->count() . "\n";
        foreach ($bg as $g) {
            echo "  - {$g->name} (rep: {$g->is_representative})\n";
        }
        
        $lg = DB::table('guests')->where('booking_id', $bookingId)->get();
        echo "\nlegacy guests: " . $lg->count() . "\n";
        foreach ($lg as $g) {
            echo "  - {$g->name} (rep: {$g->is_representative})\n";
        }
        
        $br = DB::table('booking_rooms')->where('booking_id', $bookingId)->get();
        echo "\nrooms: " . $br->count() . ", total guests expected: " . ($br->sum('adults') + $br->sum('children_0_5') + $br->sum('children_6_11')) . "\n";
    }
}
