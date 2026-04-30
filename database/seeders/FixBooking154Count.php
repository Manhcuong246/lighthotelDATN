<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixBooking154Count extends Seeder
{
    public function run(): void
    {
        echo "=== Fix Booking #154 Guest Count ===\n\n";
        
        $brs = DB::table('booking_rooms')->where('booking_id', 154)->orderBy('id')->get();
        
        echo "Before:\n";
        foreach($brs as $br) {
            echo "ID {$br->id}: Room {$br->room_id}, adults={$br->adults}, c0-5={$br->children_0_5}\n";
        }
        
        // Update room 1: adults=2, children_0_5=2
        if (isset($brs[0])) {
            DB::table('booking_rooms')->where('id', $brs[0]->id)->update([
                'adults' => 2,
                'children_0_5' => 2
            ]);
            echo "\nUpdated room 1 (ID {$brs[0]->id}): adults=2, children_0_5=2\n";
        }
        
        // Update room 2: adults=1, children_0_5=2
        if (isset($brs[1])) {
            DB::table('booking_rooms')->where('id', $brs[1]->id)->update([
                'adults' => 1,
                'children_0_5' => 2
            ]);
            echo "Updated room 2 (ID {$brs[1]->id}): adults=1, children_0_5=2\n";
        }
        
        // Verify
        $updated = DB::table('booking_rooms')->where('booking_id', 154)->get();
        echo "\nAfter:\n";
        foreach($updated as $br) {
            echo "ID {$br->id}: Room {$br->room_id}, adults={$br->adults}, c0-5={$br->children_0_5}\n";
        }
        
        $totalAdults = $updated->sum('adults');
        $totalChildren = $updated->sum('children_0_5');
        echo "\nTotal: {$totalAdults} adults + {$totalChildren} children\n";
    }
}
