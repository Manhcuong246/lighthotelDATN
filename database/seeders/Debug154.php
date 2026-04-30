<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Debug154 extends Seeder
{
    public function run(): void
    {
        $rows = DB::table('booking_rooms')->where('booking_id', 154)->get();
        echo "\n=== BOOKING #154 ===\n";
        foreach ($rows as $r) {
            echo "ID: {$r->id}, Room: {$r->room_id}, adults={$r->adults}, c0-5={$r->children_0_5}\n";
        }
        $sumAdults = $rows->sum('adults');
        $sumChild = $rows->sum('children_0_5');
        echo "TOTAL: {$sumAdults} adults, {$sumChild} children\n";
        
        // Force update
        $ids = $rows->pluck('id')->toArray();
        if (count($ids) >= 2) {
            DB::table('booking_rooms')->where('id', $ids[0])->update(['adults' => 2, 'children_0_5' => 2]);
            DB::table('booking_rooms')->where('id', $ids[1])->update(['adults' => 1, 'children_0_5' => 2]);
            echo "\nUPDATED!\n";
        }
        
        // Verify
        $rows2 = DB::table('booking_rooms')->where('booking_id', 154)->get();
        echo "\n=== AFTER UPDATE ===\n";
        foreach ($rows2 as $r) {
            echo "ID: {$r->id}, Room: {$r->room_id}, adults={$r->adults}, c0-5={$r->children_0_5}\n";
        }
        $sumAdults2 = $rows2->sum('adults');
        $sumChild2 = $rows2->sum('children_0_5');
        echo "TOTAL: {$sumAdults2} adults, {$sumChild2} children\n";
    }
}
