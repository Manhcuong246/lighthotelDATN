<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuickAddGuests146 extends Seeder
{
    public function run(): void
    {
        $bookingId = 146;
        $now = now();
        
        // Xóa guests cũ của booking (không phải representative)
        DB::table('guests')->where('booking_id', $bookingId)->where('is_representative', 0)->delete();
        DB::table('booking_guests')->where('booking_id', $bookingId)->where('is_representative', 0)->delete();
        
        // Lấy booking room IDs
        $bookingRooms = DB::table('booking_rooms')->where('booking_id', $bookingId)->get();
        $firstRoom = $bookingRooms->first();
        $secondRoom = $bookingRooms->skip(1)->first();
        
        echo "Found " . $bookingRooms->count() . " booking rooms\n";
        echo "First room ID: " . ($firstRoom?->id ?? 'N/A') . ", Room ID: " . ($firstRoom?->room_id ?? 'N/A') . "\n";
        echo "Second room ID: " . ($secondRoom?->id ?? 'N/A') . ", Room ID: " . ($secondRoom?->room_id ?? 'N/A') . "\n";
        
        // Thêm 6 guests vào bảng guests
        $guestsData = [
            ['name' => 'Nguyễn Văn An 1', 'type' => 'adult', 'room_id' => $firstRoom?->room_id, 'room_type' => 'Standard'],
            ['name' => 'Nguyễn Văn An 2', 'type' => 'adult', 'room_id' => $firstRoom?->room_id, 'room_type' => 'Standard'],
            ['name' => 'Nguyễn Văn An 3', 'type' => 'child_0_5', 'room_id' => $firstRoom?->room_id, 'room_type' => 'Standard'],
            ['name' => 'Nguyễn Văn An 4', 'type' => 'adult', 'room_id' => $secondRoom?->room_id, 'room_type' => 'Deluxe'],
            ['name' => 'Nguyễn Văn An 5', 'type' => 'adult', 'room_id' => $secondRoom?->room_id, 'room_type' => 'Deluxe'],
            ['name' => 'Nguyễn Văn An 6', 'type' => 'child_0_5', 'room_id' => $secondRoom?->room_id, 'room_type' => 'Deluxe'],
        ];
        
        foreach ($guestsData as $guest) {
            DB::table('guests')->insert([
                'booking_id' => $bookingId,
                'name' => $guest['name'],
                'cccd' => null,
                'type' => $guest['type'],
                'room_id' => $guest['room_id'],
                'room_type' => $guest['room_type'],
                'checkin_status' => 'checked_in',
                'is_representative' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        
        // Thêm vào booking_guests
        $bookingGuestsData = [
            ['name' => 'Nguyễn Văn An 1', 'type' => 'adult', 'booking_room_id' => $firstRoom?->id],
            ['name' => 'Nguyễn Văn An 2', 'type' => 'adult', 'booking_room_id' => $firstRoom?->id],
            ['name' => 'Nguyễn Văn An 3', 'type' => 'child', 'booking_room_id' => $firstRoom?->id],
            ['name' => 'Nguyễn Văn An 4', 'type' => 'adult', 'booking_room_id' => $secondRoom?->id],
            ['name' => 'Nguyễn Văn An 5', 'type' => 'adult', 'booking_room_id' => $secondRoom?->id],
            ['name' => 'Nguyễn Văn An 6', 'type' => 'child', 'booking_room_id' => $secondRoom?->id],
        ];
        
        foreach ($bookingGuestsData as $guest) {
            DB::table('booking_guests')->insert([
                'booking_id' => $bookingId,
                'booking_room_id' => $guest['booking_room_id'],
                'name' => $guest['name'],
                'cccd' => null,
                'type' => $guest['type'],
                'status' => 'checked_in',
                'checkin_status' => 'checked_in',
                'is_representative' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        
        echo "Added 6 guests to booking #$bookingId\n";
    }
}
