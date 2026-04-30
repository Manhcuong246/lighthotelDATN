<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResetBooking151 extends Seeder
{
    public function run(): void
    {
        $bookingId = 151;
        
        // Xóa tất cả booking_guests của booking 151
        DB::table('booking_guests')->where('booking_id', $bookingId)->delete();
        echo "Deleted all booking_guests for booking #$bookingId\n";
        
        // Thêm lại chỉ người đại diện từ booking info
        $bookingRoomId = DB::table('booking_rooms')->where('booking_id', $bookingId)->value('id');
        
        DB::table('booking_guests')->insert([
            'booking_id' => $bookingId,
            'booking_room_id' => $bookingRoomId,
            'name' => 'Lê Đức Trung',
            'cccd' => '097654765644',
            'type' => 'adult',
            'status' => 'checked_in',
            'checkin_status' => 'checked_in',
            'is_representative' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "Added representative guest only\n";
        echo "Done. Please test check-in form again.\n";
    }
}
