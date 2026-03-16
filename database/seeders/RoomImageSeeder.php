<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\Image;

class RoomImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cập nhật ảnh đại diện cho các phòng
        $sampleImages = [
            'https://images.pexels.com/photos/164595/pexels-photo-164595.jpeg?auto=compress&cs=tinysrgb&w=800',
            'https://images.pexels.com/photos/271619/pexels-photo-271619.jpeg?auto=compress&cs=tinysrgb&w=800',
            'https://images.pexels.com/photos/262048/pexels-photo-262048.jpeg?auto=compress&cs=tinysrgb&w=800',
            'https://images.pexels.com/photos/279746/pexels-photo-279746.jpeg?auto=compress&cs=tinysrgb&w=800',
        ];

        $rooms = Room::all();
        foreach ($rooms as $index => $room) {
            // Cập nhật ảnh đại diện
            $room->update([
                'image' => $sampleImages[$index % count($sampleImages)]
            ]);

            // Tạo thêm ảnh chi tiết cho phòng đầu tiên
            if ($index === 0) {
                Image::create([
                    'room_id' => $room->id,
                    'image_url' => $sampleImages[0],
                    'image_type' => 'room'
                ]);
                Image::create([
                    'room_id' => $room->id,
                    'image_url' => $sampleImages[1],
                    'image_type' => 'room'
                ]);
            }
        }

        echo "Updated {$rooms->count()} rooms with images\n";
    }
}
