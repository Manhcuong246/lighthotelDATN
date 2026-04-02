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
        ['name' => 'Phòng đơn', 'status' => 1, 'capacity' => 2, 'price' => 1000000],
        ['name' => 'Phòng đôi', 'status' => 1, 'capacity' => 4, 'price' => 1500000],
        ['name' => 'Phòng VIP', 'status' => 1, 'capacity' => 6, 'price' => 2500000],
        ['name' => 'Phòng gia đình', 'status' => 0, 'capacity' => 8, 'price' => 3000000],
    ]);
}
}
