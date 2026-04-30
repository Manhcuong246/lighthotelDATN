<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixBooking149Display extends Seeder
{
    public function run(): void
    {
        $bookingId = 149;
        $now = now();
        
        // Thêm Nguyễn Văn Bách vào booking_guests nếu chưa có
        $exists = DB::table('booking_guests')
            ->where('booking_id', $bookingId)
            ->where('name', 'Nguyễn Văn Bách')
            ->exists();
        
        if (!$exists) {
            $bookingRooms = DB::table('booking_rooms')->where('booking_id', $bookingId)->get();
            $secondRoom = $bookingRooms->skip(1)->first();
            
            DB::table('booking_guests')->insert([
                'booking_id' => $bookingId,
                'booking_room_id' => $secondRoom?->id,
                'name' => 'Nguyễn Văn Bách',
                'cccd' => null,
                'type' => 'adult',
                'status' => 'checked_in',
                'checkin_status' => 'checked_in',
                'is_representative' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            echo "Added Nguyễn Văn Bách to booking_guests for booking #$bookingId\n";
        } else {
            echo "Nguyễn Văn Bách already exists in booking_guests\n";
        }
    }
}
