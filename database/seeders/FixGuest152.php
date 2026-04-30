<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixGuest152 extends Seeder
{
    public function run(): void
    {
        $bookingId = 152;
        
        echo "=== Booking #$bookingId Guest Check ===\n\n";
        
        // Check current guests
        $guests = DB::table('booking_guests')->where('booking_id', $bookingId)->get();
        echo "Current booking_guests count: " . $guests->count() . "\n";
        foreach ($guests as $g) {
            echo "  ID:{$g->id} Name:{$g->name} Rep:{$g->is_representative}\n";
        }
        
        // Keep only one guest (the representative with lowest ID)
        if ($guests->count() > 1) {
            $keepId = $guests->where('is_representative', 1)->first()?->id ?? $guests->first()->id;
            echo "\nKeeping ID: $keepId\n";
            
            $deleted = DB::table('booking_guests')
                ->where('booking_id', $bookingId)
                ->where('id', '!=', $keepId)
                ->delete();
            echo "Deleted: $deleted\n";
        }
        
        // Also check legacy guests
        $legacy = DB::table('guests')->where('booking_id', $bookingId)->get();
        echo "\nLegacy guests count: " . $legacy->count() . "\n";
        
        if ($legacy->count() > 1) {
            $keepId = $legacy->where('is_representative', 1)->first()?->id ?? $legacy->first()->id;
            $deleted = DB::table('guests')
                ->where('booking_id', $bookingId)
                ->where('id', '!=', $keepId)
                ->delete();
            echo "Deleted legacy: $deleted\n";
        }
        
        echo "\nDone!\n";
    }
}
