<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixCccd149 extends Seeder
{
    public function run(): void
    {
        $bookingId = 149;
        
        // Lấy CCCD từ legacy guests
        $legacyGuest = DB::table('guests')
            ->where('booking_id', $bookingId)
            ->where('name', 'Nguyễn Văn Bách')
            ->first();
        
        if ($legacyGuest && $legacyGuest->cccd) {
            DB::table('booking_guests')
                ->where('booking_id', $bookingId)
                ->where('name', 'Nguyễn Văn Bách')
                ->update(['cccd' => $legacyGuest->cccd]);
            
            echo "Updated CCCD for Nguyễn Văn Bách: {$legacyGuest->cccd}\n";
        } else {
            echo "No CCCD found in legacy guests\n";
        }
    }
}
