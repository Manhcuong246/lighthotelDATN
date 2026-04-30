<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateBooking154Guests extends Seeder
{
    public function run(): void
    {
        echo "=== Updating Booking #154 Guest Count ===\n\n";

        // Lấy booking rooms hiện tại
        $bookingRooms = DB::table('booking_rooms')
            ->where('booking_id', 154)
            ->orderBy('id')
            ->get();

        // Cập nhật: Phòng 1 có 2 adults + 2 trẻ, Phòng 2 có 1 adult + 2 trẻ
        // Tổng = 3 người lớn, 4 trẻ em
        $rooms = $bookingRooms->toArray();

        if (count($rooms) >= 1) {
            DB::table('booking_rooms')
                ->where('id', $rooms[0]->id)
                ->update(['adults' => 2, 'children_0_5' => 2]);
            echo "Updated room 1: adults=2, children_0_5=2\n";
        }

        if (count($rooms) >= 2) {
            DB::table('booking_rooms')
                ->where('id', $rooms[1]->id)
                ->update(['adults' => 1, 'children_0_5' => 2]);
            echo "Updated room 2: adults=1, children_0_5=2\n";
        }

        // Kiểm tra sau khi cập nhật
        $updatedRooms = DB::table('booking_rooms')
            ->where('booking_id', 154)
            ->get();

        $totalAdults = $updatedRooms->sum('adults');
        $totalChildren05 = $updatedRooms->sum('children_0_5');

        echo "\n=== TOTALS ===\n";
        echo "Người lớn: {$totalAdults}\n";
        echo "Trẻ em: {$totalChildren05}\n";
    }
}
