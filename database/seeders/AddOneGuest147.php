<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddOneGuest147 extends Seeder
{
    public function run(): void
    {
        $bookingId = 147;
        $now = now();
        
        // Lấy booking room đầu tiên
        $bookingRoom = DB::table('booking_rooms')->where('booking_id', $bookingId)->first();
        $roomId = $bookingRoom?->room_id ?? 6;
        
        // Thêm 1 khách Nguyễn Văn An
        DB::table('guests')->insert([
            'booking_id' => $bookingId,
            'name' => 'Nguyễn Văn An',
            'cccd' => null,
            'type' => 'adult',
            'room_id' => $roomId,
            'checkin_status' => 'checked_in',
            'is_representative' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        DB::table('booking_guests')->insert([
            'booking_id' => $bookingId,
            'booking_room_id' => $bookingRoom?->id,
            'name' => 'Nguyễn Văn An',
            'cccd' => null,
            'type' => 'adult',
            'status' => 'checked_in',
            'checkin_status' => 'checked_in',
            'is_representative' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        echo "Added 1 guest (Nguyễn Văn An) to booking #$bookingId\n";
    }
}
