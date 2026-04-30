<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixBooking150Final extends Seeder
{
    public function run(): void
    {
        $bookingId = 150;
        
        echo "=== CHECK BOOKING #$bookingId ===\n\n";
        
        // 1. Check booking_guests
        $bgGuests = DB::table('booking_guests')->where('booking_id', $bookingId)->get();
        echo "booking_guests count: " . $bgGuests->count() . "\n";
        foreach ($bgGuests as $g) {
            echo "  - {$g->name} (rep={$g->is_representative}, room_id={$g->booking_room_id})\n";
        }
        
        // 2. Check legacy guests
        $lgGuests = DB::table('guests')->where('booking_id', $bookingId)->get();
        echo "\nguests (legacy) count: " . $lgGuests->count() . "\n";
        foreach ($lgGuests as $g) {
            echo "  - {$g->name} (rep={$g->is_representative})\n";
        }
        
        // 3. Check booking rooms
        echo "\n=== BOOKING ROOMS ===\n";
        $bRooms = DB::table('booking_rooms')
            ->join('rooms', 'booking_rooms.room_id', '=', 'rooms.id')
            ->where('booking_rooms.booking_id', $bookingId)
            ->select('booking_rooms.id', 'rooms.name as room_name', 'rooms.room_number')
            ->get();
        foreach ($bRooms as $br) {
            echo "  booking_room.id={$br->id}, room_name={$br->room_name}, room_number={$br->room_number}\n";
        }
        
        // 4. SYNC: If legacy has more guests than booking_guests, add missing
        if ($lgGuests->count() > $bgGuests->count()) {
            echo "\n=== SYNCING MISSING GUESTS ===\n";
            $existingNames = $bgGuests->pluck('name')->toArray();
            $firstRoomId = $bRooms->first()?->id;
            
            foreach ($lgGuests as $lg) {
                if (!in_array($lg->name, $existingNames)) {
                    DB::table('booking_guests')->insert([
                        'booking_id' => $bookingId,
                        'booking_room_id' => $firstRoomId,
                        'name' => $lg->name,
                        'cccd' => $lg->cccd,
                        'type' => $lg->type ?? 'adult',
                        'status' => $lg->checkin_status ?? 'checked_in',
                        'checkin_status' => $lg->checkin_status ?? 'checked_in',
                        'is_representative' => $lg->is_representative ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    echo "  ADDED: {$lg->name}\n";
                }
            }
        }
        
        // 5. FINAL COUNT
        $finalCount = DB::table('booking_guests')->where('booking_id', $bookingId)->count();
        echo "\n=== FINAL booking_guests count: $finalCount ===\n";
    }
}
