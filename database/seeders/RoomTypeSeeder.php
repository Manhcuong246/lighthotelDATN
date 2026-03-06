<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RoomType;

class RoomTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
{
    RoomType::insert([
        ['name' => 'Phòng đơn', 'status' => 1],
        ['name' => 'Phòng đôi', 'status' => 1],
        ['name' => 'Phòng VIP', 'status' => 1],
        ['name' => 'Phòng gia đình', 'status' => 0],
    ]);
}
}
