<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixCccd151 extends Seeder
{
    public function run(): void
    {
        $bookingId = 151;
        $cccd = '097654765644'; // CCCD đúng
        
        echo "=== Fixing CCCD for Booking #$bookingId ===\n\n";
        
        // 1. Update booking.cccd
        DB::table('bookings')->where('id', $bookingId)->update(['cccd' => $cccd]);
        echo "Updated bookings.cccd\n";
        
        // 2. Update booking_guests for representative
        DB::table('booking_guests')
            ->where('booking_id', $bookingId)
            ->where('is_representative', 1)
            ->update(['cccd' => $cccd]);
        echo "Updated booking_guests.cccd for representative\n";
        
        // 3. Update legacy guests
        DB::table('guests')
            ->where('booking_id', $bookingId)
            ->where('is_representative', 1)
            ->update(['cccd' => $cccd]);
        echo "Updated guests.cccd for representative\n";
        
        // 4. Verify
        $booking = DB::table('bookings')->where('id', $bookingId)->first();
        echo "\nVerification:\n";
        echo "  bookings.cccd: " . ($booking->cccd ?? 'NULL') . "\n";
        
        $bg = DB::table('booking_guests')->where('booking_id', $bookingId)->where('is_representative', 1)->first();
        echo "  booking_guests.cccd: " . ($bg?->cccd ?? 'NULL') . "\n";
        
        $lg = DB::table('guests')->where('booking_id', $bookingId)->where('is_representative', 1)->first();
        echo "  guests.cccd: " . ($lg?->cccd ?? 'NULL') . "\n";
        
        echo "\nDone! Please refresh the page.\n";
    }
}
