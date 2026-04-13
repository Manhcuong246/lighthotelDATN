<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateRoomNumbersSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = DB::table('rooms')->get();
        foreach ($rooms as $room) {
            DB::table('rooms')->where('id', $room->id)->update(['room_number' => $room->name]);
        }
        echo "Updated " . count($rooms) . " rooms with room numbers\n";
    }
}
