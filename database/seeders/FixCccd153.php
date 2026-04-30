<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixCccd153 extends Seeder
{
    public function run(): void
    {
        $bookingId = 153;
        
        echo "=== Fixing CCCD for Booking #$bookingId ===\n\n";
        
        // Get CCCD from legacy guest
        $guest = DB::table('guests')
            ->where('booking_id', $bookingId)
            ->where('is_representative', 1)
            ->first();
        
        if ($guest && $guest->cccd) {
            echo "Found CCCD in legacy guest: {$guest->cccd}\n";
            
            // Update booking.cccd
            DB::table('bookings')->where('id', $bookingId)->update(['cccd' => $guest->cccd]);
            echo "Updated bookings.cccd\n";
            
            // Also update booking_guests if exists
            DB::table('booking_guests')
                ->where('booking_id', $bookingId)
                ->where('is_representative', 1)
                ->update(['cccd' => $guest->cccd]);
            echo "Updated booking_guests.cccd\n";
            
            echo "\nDone! Please refresh the page.\n";
        } else {
            echo "No CCCD found in legacy guests!\n";
        }
    }
}
