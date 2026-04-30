<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DebugBooking154Guests extends Seeder
{
    public function run(): void
    {
        echo "=== Booking #154 Guest Count Debug ===\n\n";
        
        $bookingRooms = DB::table('booking_rooms')
            ->where('booking_id', 154)
            ->get();
        
        echo "Booking Rooms:\n";
        $totalAdults = 0;
        $totalChildren611 = 0;
        $totalChildren05 = 0;
        
        foreach ($bookingRooms as $index => $br) {
            echo ($index + 1) . ". Room ID: {$br->room_id}\n";
            echo "   Adults: {$br->adults}\n";
            echo "   Children 6-11: {$br->children_6_11}\n";
            echo "   Children 0-5: {$br->children_0_5}\n\n";
            
            $totalAdults += $br->adults;
            $totalChildren611 += $br->children_6_11;
            $totalChildren05 += $br->children_0_5;
        }
        
        echo "=== TOTALS ===\n";
        echo "Adults: {$totalAdults}\n";
        echo "Children 6-11: {$totalChildren611}\n";
        echo "Children 0-5: {$totalChildren05}\n";
        echo "\n";
        echo "Người lớn (adults + 6-11): " . ($totalAdults + $totalChildren611) . "\n";
        echo "Trẻ em (0-5): {$totalChildren05}\n";
    }
}
