<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\HotelInfo;
use App\Models\Room;
use App\Models\Amenity;
use App\Models\Service;
use App\Models\SiteContent;
use App\Models\Coupon;

class HotelDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create hotel info
        HotelInfo::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Light Hotel',
                'description' => 'Khách sạn sang trọng hàng đầu với dịch vụ 5 sao và tiện nghi hiện đại.',
                'address' => 'Khu trung tâm, Việt Nam',
                'phone' => '+84 123 456 789',
                'email' => 'info@lighthotel.com',
                'latitude' => 10.776944,
                'longitude' => 106.700973,
            ]
        );

        // Create rooms
        $rooms = [
            [
                'name' => 'Phòng Deluxe',
                'type' => 'Deluxe',
                'base_price' => 1500000,
                'max_guests' => 2,
                'beds' => 1,
                'baths' => 1,
                'area' => 35,
                'description' => 'Phòng sang trọng với view thành phố',
                'status' => 'available',
            ],
            [
                'name' => 'Phòng Suite',
                'type' => 'Suite',
                'base_price' => 2500000,
                'max_guests' => 3,
                'beds' => 2,
                'baths' => 1,
                'area' => 50,
                'description' => 'Phòng suite rộng rãi với phòng khách riêng',
                'status' => 'available',
            ],
            [
                'name' => 'Phòng Executive',
                'type' => 'Executive',
                'base_price' => 3500000,
                'max_guests' => 4,
                'beds' => 2,
                'baths' => 2,
                'area' => 70,
                'description' => 'Phòng cao cấp nhất với đầy đủ tiện nghi',
                'status' => 'available',
            ],
        ];

        foreach ($rooms as $room) {
         Room::firstOrCreate(['name' => $room['name']], $room);
        }

        // Create amenities
        $amenities = [
            ['name' => 'WiFi', 'icon_url' => 'wifi.png'],
            ['name' => 'Hồ bơi', 'icon_url' => 'pool.png'],
            ['name' => 'Gym', 'icon_url' => 'gym.png'],
            ['name' => 'Spa', 'icon_url' => 'spa.png'],
            ['name' => 'Nhà hàng', 'icon_url' => 'restaurant.png'],
            ['name' => 'Bar', 'icon_url' => 'bar.png'],
            ['name' => 'Đỗ xe miễn phí', 'icon_url' => 'parking.png'],
            ['name' => 'Điều hòa', 'icon_url' => 'ac.png'],
        ];

        foreach ($amenities as $amenity) {
            Amenity::firstOrCreate(['name' => $amenity['name']], $amenity);
        }

        // Create services
        $services = [
            ['name' => 'Đón sân bay', 'price' => 500000, 'description' => 'Dịch vụ đón từ sân bay về khách sạn'],
            ['name' => 'Giặt ủi', 'price' => 100000, 'description' => 'Dịch vụ giặt ủi nhanh chóng'],
            ['name' => 'Room Service', 'price' => 150000, 'description' => 'Phục vụ ăn uống tại phòng 24/7'],
            ['name' => 'Tour du lịch', 'price' => 1000000, 'description' => 'Tour tham quan thành phố'],
        ];

        foreach ($services as $service) {
         Service::firstOrCreate(['name' => $service['name']], $service);
        }

        // Create site content
        SiteContent::firstOrCreate(
            ['type' => 'banner'],
            [
                'title' => 'Chào mừng đến với Light Hotel',
                'content' => 'Trải nghiệm nghỉ dưỡng đẳng cấp',
                'image_url' => 'banner.jpg',
                'is_active' => true,
            ]
        );

        SiteContent::firstOrCreate(
            ['type' => 'about'],
            [
                'title' => 'Về Light Hotel',
                'content' => 'Light Hotel là khách sạn 5 sao hàng đầu với dịch vụ chuyên nghiệp và tiện nghi hiện đại.',
                'is_active' => true,
            ]
        );

        // Create coupon
        Coupon::firstOrCreate(
            ['code' => 'WELCOME10'],
            [
                'discount_percent' => 10,
                'expired_at' => now()->addMonths(6),
                'is_active' => true,
            ]
        );
    }
}
