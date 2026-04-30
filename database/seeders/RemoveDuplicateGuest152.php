<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RemoveDuplicateGuest152 extends Seeder
{
    public function run(): void
    {
        $bookingId = 152;

        echo "=== Removing duplicate guests for Booking #$bookingId ===\n\n";

        // Xóa khách không phải đại diện (is_representative = 0)
        $deleted = DB::table('booking_guests')
            ->where('booking_id', $bookingId)
            ->where('is_representative', 0)
            ->delete();

        echo "Deleted $deleted non-representative guests\n";

        // Giữ lại chỉ 1 người đại diện
        $repGuests = DB::table('booking_guests')
            ->where('booking_id', $bookingId)
            ->where('is_representative', 1)
            ->get();

        if ($repGuests->count() > 1) {
            // Giữ lại bản ghi đầu tiên, xóa các bản ghi sau
            $firstId = $repGuests->first()->id;
            $deleted = DB::table('booking_guests')
                ->where('booking_id', $bookingId)
                ->where('is_representative', 1)
                ->where('id', '!=', $firstId)
                ->delete();
            echo "Deleted $deleted duplicate representative guests\n";
        }

        // Xóa legacy guests không phải đại diện
        $deleted = DB::table('guests')
            ->where('booking_id', $bookingId)
            ->where('is_representative', 0)
            ->delete();
        echo "Deleted $deleted non-representative legacy guests\n";

        // Final count
        $finalCount = DB::table('booking_guests')->where('booking_id', $bookingId)->count();
        echo "\nFinal guest count: $finalCount\n";
    }
}
