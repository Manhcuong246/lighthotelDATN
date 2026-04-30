<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixGuestNames146 extends Seeder
{
    public function run(): void
    {
        $bookingId = 146;
        
        // Update guests table - đổi tất cả thành "Nguyễn Văn An" không có số
        DB::table('guests')
            ->where('booking_id', $bookingId)
            ->where('is_representative', 0)
            ->update(['name' => 'Nguyễn Văn An']);
        
        // Update booking_guests table - đổi tất cả thành "Nguyễn Văn An" không có số
        DB::table('booking_guests')
            ->where('booking_id', $bookingId)
            ->where('is_representative', 0)
            ->update(['name' => 'Nguyễn Văn An']);
        
        echo "Updated all 6 guest names to 'Nguyễn Văn An' for booking #$bookingId\n";
    }
}
