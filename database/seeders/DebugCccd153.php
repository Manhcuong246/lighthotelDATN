<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DebugCccd153 extends Seeder
{
    public function run(): void
    {
        $b = DB::table('bookings')->where('id', 153)->first();
        echo "booking.cccd: " . ($b->cccd ?? 'NULL') . "\n";
        echo "user_id: " . ($b->user_id ?? 'NULL') . "\n";
        
        if ($b->user_id) {
            $u = DB::table('users')->where('id', $b->user_id)->first();
            echo "user.cccd: " . ($u->cccd ?? 'NULL') . "\n";
            echo "user.identity_card: " . ($u->identity_card ?? 'NULL') . "\n";
            echo "user.cmnd: " . ($u->cmnd ?? 'NULL') . "\n";
        }
    }
}
