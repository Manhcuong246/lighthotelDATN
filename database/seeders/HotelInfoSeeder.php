<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HotelInfo;

class HotelInfoSeeder extends Seeder
{
    public function run(): void
    {
        HotelInfo::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Light Hotel',
                'description' => 'Khách sạn Light Hotel - Nơi nghỉ dưỡng tuyệt vờii',
                'address' => 'Khu trung tâm, Việt Nam',
                'phone' => '0901234567',
                'email' => 'contact@lighthotel.com',
                'bank_id' => 'mbbank',
                'bank_account' => '0326083913',
                'bank_account_name' => 'LE DUC TRUNG',
                'latitude' => 10.762622,
                'longitude' => 106.660172,
            ]
        );

        echo "Hotel info seeded successfully\n";
    }
}
