<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeleteExtraGuest152 extends Seeder
{
    public function run(): void
    {
        $bookingId = 152;
        
        // Xóa tất cả trừ ID nhỏ nhất
        $ids = DB::table('booking_guests')->where('booking_id', $bookingId)->orderBy('id')->pluck('id');
        if ($ids->count() > 1) {
            $keepId = $ids->first();
            $deleted = DB::table('booking_guests')
                ->where('booking_id', $bookingId)
                ->where('id', '!=', $keepId)
                ->delete();
            echo "Deleted $deleted extra guests. Kept ID: $keepId\n";
        } else {
            echo "Only 1 guest exists\n";
        }
        
        echo "Final count: " . DB::table('booking_guests')->where('booking_id', $bookingId)->count() . "\n";
    }
}
