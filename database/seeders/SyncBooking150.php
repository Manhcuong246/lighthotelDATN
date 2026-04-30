<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SyncBooking150 extends Seeder
{
    public function run(): void
    {
        $bookingId = 150;
        $now = now();
        
        // Lấy dữ liệu
        $legacyGuests = DB::table('guests')->where('booking_id', $bookingId)->get();
        $bookingGuests = DB::table('booking_guests')->where('booking_id', $bookingId)->get();
        $bookingRooms = DB::table('booking_rooms')->where('booking_id', $bookingId)->get();
        
        echo "Before: BG=" . $bookingGuests->count() . ", LG=" . $legacyGuests->count() . "\n";
        
        $existingNames = $bookingGuests->pluck('name')->toArray();
        
        foreach ($legacyGuests as $lg) {
            if (!in_array($lg->name, $existingNames)) {
                $roomIndex = $lg->room_index ?? 0;
                $bookingRoom = $bookingRooms->skip($roomIndex)->first() ?? $bookingRooms->first();
                
                DB::table('booking_guests')->insert([
                    'booking_id' => $bookingId,
                    'booking_room_id' => $bookingRoom?->id,
                    'name' => $lg->name,
                    'cccd' => $lg->cccd,
                    'type' => $lg->type ?? 'adult',
                    'status' => $lg->checkin_status ?? 'pending',
                    'checkin_status' => $lg->checkin_status ?? 'pending',
                    'is_representative' => $lg->is_representative ?? 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                echo "Added: {$lg->name}\n";
            }
        }
        
        $finalCount = DB::table('booking_guests')->where('booking_id', $bookingId)->count();
        echo "After: BG=$finalCount\n";
    }
}
