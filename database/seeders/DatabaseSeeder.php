<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            GuestUserSeeder::class,
            RoomTypeSeeder::class,
            HotelDataSeeder::class,
            BookingSeeder::class, // sample bookings for testing
        ]);
    }
}
