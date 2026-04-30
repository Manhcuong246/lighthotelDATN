<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CheckGuests150 extends Seeder
{
    public function run(): void
    {
        $bookingId = 150;
        
        echo "=== BOOKING_GUESTS ===\n";
        $bg = DB::table('booking_guests')->where('booking_id', $bookingId)->get();
        foreach ($bg as $g) {
            echo "ID:{$g->id} | Name:{$g->name} | CCCD:{$g->cccd} | Rep:{$g->is_representative}\n";
        }
        
        echo "\n=== GUESTS (LEGACY) ===\n";
        $lg = DB::table('guests')->where('booking_id', $bookingId)->get();
        foreach ($lg as $g) {
            echo "ID:{$g->id} | Name:{$g->name} | CCCD:{$g->cccd} | Rep:{$g->is_representative}\n";
        }
    }
}
