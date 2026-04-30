<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddMissingGuests147 extends Seeder
{
    public function run(): void
    {
        $bookingId = 147;
        $now = now();
        
        // Xóa guests cũ (không phải đại diện) để thêm lại cho đúng
        DB::table('booking_guests')->where('booking_id', $bookingId)->where('is_representative', 0)->delete();
        DB::table('guests')->where('booking_id', $bookingId)->where('is_representative', 0)->delete();
        
        // Lấy booking room IDs
        $bookingRooms = DB::table('booking_rooms')->where('booking_id', $bookingId)->get();
        $firstRoom = $bookingRooms->first();
        $secondRoom = $bookingRooms->skip(1)->first();
        
        // Thêm 5 khách mới (tổng 6 người với đại diện)
        $guests = [
            // Standard 102 (Room ID 6) - thêm 2 người nữa
            ['name' => 'Nguyễn Văn A', 'type' => 'adult', 'room_id' => 6, 'booking_room_id' => $firstRoom?->id],
            ['name' => 'Nguyễn Văn B', 'type' => 'child_0_5', 'room_id' => 6, 'booking_room_id' => $firstRoom?->id],
            
            // Deluxe (Room ID 10) - thêm 3 người  
            ['name' => 'Nguyễn Văn C', 'type' => 'adult', 'room_id' => 10, 'booking_room_id' => $secondRoom?->id],
            ['name' => 'Nguyễn Văn D', 'type' => 'adult', 'room_id' => 10, 'booking_room_id' => $secondRoom?->id],
            ['name' => 'Nguyễn Văn E', 'type' => 'child_6_11', 'room_id' => 10, 'booking_room_id' => $secondRoom?->id],
        ];
        
        foreach ($guests as $guest) {
            // Legacy guests table
            DB::table('guests')->insert([
                'booking_id' => $bookingId,
                'name' => $guest['name'],
                'cccd' => null,
                'type' => $guest['type'],
                'room_id' => $guest['room_id'],
                'checkin_status' => 'checked_in',
                'is_representative' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            
            // Booking guests table
            DB::table('booking_guests')->insert([
                'booking_id' => $bookingId,
                'booking_room_id' => $guest['booking_room_id'],
                'name' => $guest['name'],
                'cccd' => null,
                'type' => str_contains($guest['type'], 'adult') ? 'adult' : 'child',
                'status' => 'checked_in',
                'checkin_status' => 'checked_in',
                'is_representative' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        
        echo "Added 5 guests to booking #$bookingId (total 6 guests with representative)\n";
    }
}
