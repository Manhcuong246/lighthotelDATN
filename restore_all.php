<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$bookingId = 154;
$now = now();

// Xóa khách cũ nếu có
echo "Clearing old guests...\n";
DB::table('booking_guests')->where('booking_id', $bookingId)->delete();
DB::table('guests')->where('booking_id', $bookingId)->delete();

// Danh sách khách cần thêm
$guests = [
    // Người đại diện - Standard 104
    [
        'name' => 'Nguyễn Thu Thủy',
        'cccd' => '089765456789',
        'type' => 'adult',
        'is_representative' => 1,
        'booking_room_id' => 138,
        'room_id' => 15,
    ],
    // 2 người lớn - Standard 104
    [
        'name' => 'Nguyễn Thị Hạnh',
        'cccd' => '098765456789',
        'type' => 'adult',
        'is_representative' => 0,
        'booking_room_id' => 138,
        'room_id' => 15,
    ],
    [
        'name' => 'Nguyễn Quốc Việt',
        'cccd' => '098765467897',
        'type' => 'adult',
        'is_representative' => 0,
        'booking_room_id' => 138,
        'room_id' => 15,
    ],
    // 1 trẻ em - Standard 104
    [
        'name' => 'Nguyễn Thanh Thảo',
        'cccd' => null,
        'type' => 'child',
        'is_representative' => 0,
        'booking_room_id' => 138,
        'room_id' => 15,
    ],
    // 2 trẻ em - Deluxe 206
    [
        'name' => 'Hoàng Văn Bách',
        'cccd' => null,
        'type' => 'child',
        'is_representative' => 0,
        'booking_room_id' => 139,
        'room_id' => 7,
    ],
    [
        'name' => 'Vũ Trọng Khải',
        'cccd' => null,
        'type' => 'child',
        'is_representative' => 0,
        'booking_room_id' => 139,
        'room_id' => 7,
    ],
];

echo "Adding guests...\n";
foreach ($guests as $guest) {
    // Thêm vào booking_guests
    $bgId = DB::table('booking_guests')->insertGetId([
        'booking_id' => $bookingId,
        'booking_room_id' => $guest['booking_room_id'],
        'name' => $guest['name'],
        'cccd' => $guest['cccd'],
        'type' => $guest['type'],
        'is_representative' => $guest['is_representative'],
        'status' => 'checked_in',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Thêm vào guests (legacy)
    DB::table('guests')->insert([
        'booking_id' => $bookingId,
        'booking_guest_id' => $bgId,
        'name' => $guest['name'],
        'cccd' => $guest['cccd'],
        'type' => $guest['type'],
        'is_representative' => $guest['is_representative'],
        'room_id' => $guest['room_id'],
        'checkin_status' => 'checked_in',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    echo "  Added: {$guest['name']} - " . ($guest['type'] === 'adult' ? 'Người lớn' : 'Trẻ em') . " - Room: {$guest['room_id']}\n";
}

echo "\nDone! Added " . count($guests) . " guests.\n";
