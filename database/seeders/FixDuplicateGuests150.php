<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixDuplicateGuests150 extends Seeder
{
    public function run(): void
    {
        $bookingId = 150;
        
        // Xóa bản ghi trùng lặp (giữ lại bản ghi có ID nhỏ nhất)
        $duplicates = DB::table('booking_guests')
            ->where('booking_id', $bookingId)
            ->where('name', 'Lê Đức Trung')
            ->orderBy('id')
            ->get();
        
        echo "Found " . $duplicates->count() . " records for 'Lê Đức Trung'\n";
        
        if ($duplicates->count() > 1) {
            $keepId = $duplicates->first()->id;
            echo "Keeping ID: $keepId\n";
            
            foreach ($duplicates as $dup) {
                if ($dup->id !== $keepId) {
                    DB::table('booking_guests')->where('id', $dup->id)->delete();
                    echo "Deleted duplicate ID: {$dup->id}\n";
                }
            }
        }
        
        // Thêm Nguyễn Văn Chiến nếu thiếu
        $hasChien = DB::table('booking_guests')
            ->where('booking_id', $bookingId)
            ->where('name', 'like', '%Chiến%')
            ->exists();
        
        if (!$hasChien) {
            $firstRoomId = DB::table('booking_rooms')
                ->where('booking_id', $bookingId)
                ->value('id');
            
            DB::table('booking_guests')->insert([
                'booking_id' => $bookingId,
                'booking_room_id' => $firstRoomId,
                'name' => 'Nguyễn Văn Chiến',
                'cccd' => '098978675434',
                'type' => 'adult',
                'status' => 'checked_in',
                'checkin_status' => 'checked_in',
                'is_representative' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "Added Nguyễn Văn Chiến\n";
        } else {
            echo "Nguyễn Văn Chiến already exists\n";
        }
    }
}
