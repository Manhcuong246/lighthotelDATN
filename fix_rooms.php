<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$bookingId = 154;
$now = now();

// Xóa khách cũ
DB::table('booking_guests')->where('booking_id', $bookingId)->delete();
DB::table('guests')->where('booking_id', $bookingId)->delete();

// Xóa booking_rooms cũ
DB::table('booking_rooms')->where('booking_id', $bookingId)->delete();

// Thêm 3 phòng mới
$br104 = DB::table('booking_rooms')->insertGetId([
    'booking_id' => $bookingId,
    'room_id' => 15, // Standard 104
    'check_in' => '2026-04-30',
    'check_out' => '2026-05-01',
    'price' => 1050000,
    'created_at' => $now,
    'updated_at' => $now,
]);

$br206 = DB::table('booking_rooms')->insertGetId([
    'booking_id' => $bookingId,
    'room_id' => 7, // Deluxe 206
    'check_in' => '2026-04-30',
    'check_out' => '2026-05-01',
    'price' => 1250000,
    'created_at' => $now,
    'updated_at' => $now,
]);

$br207 = DB::table('booking_rooms')->insertGetId([
    'booking_id' => $bookingId,
    'room_id' => 11, // Deluxe 207
    'check_in' => '2026-04-30',
    'check_out' => '2026-05-01',
    'price' => 1250000,
    'created_at' => $now,
    'updated_at' => $now,
]);

echo "Added 3 rooms: br104={$br104}, br206={$br206}, br207={$br207}\n";

// Danh sách khách theo yêu cầu:
// Standard 104: 1 người lớn (đại diện) + 2 trẻ em
// Deluxe 206: 1 người lớn + 1 trẻ em
// Deluxe 207: 1 người lớn + 1 trẻ em

$guests = [
    // Standard 104 - Người đại diện + 2 trẻ em
    ['name' => 'Nguyễn Thu Thủy', 'cccd' => '089765456789', 'type' => 'adult', 'is_rep' => 1, 'br_id' => $br104, 'room_id' => 15],
    ['name' => 'Nguyễn Thanh Thảo', 'cccd' => null, 'type' => 'child', 'is_rep' => 0, 'br_id' => $br104, 'room_id' => 15],
    ['name' => 'Hoàng Văn Bách', 'cccd' => null, 'type' => 'child', 'is_rep' => 0, 'br_id' => $br104, 'room_id' => 15],
    
    // Deluxe 206 - 1 người lớn + 1 trẻ em
    ['name' => 'Nguyễn Thị Hạnh', 'cccd' => '098765456789', 'type' => 'adult', 'is_rep' => 0, 'br_id' => $br206, 'room_id' => 7],
    ['name' => 'Vũ Trọng Khải', 'cccd' => null, 'type' => 'child', 'is_rep' => 0, 'br_id' => $br206, 'room_id' => 7],
    
    // Deluxe 207 - 1 người lớn + 1 trẻ em
    ['name' => 'Nguyễn Quốc Việt', 'cccd' => '098765467897', 'type' => 'adult', 'is_rep' => 0, 'br_id' => $br207, 'room_id' => 11],
    ['name' => 'Nguyễn Thị Lan', 'cccd' => null, 'type' => 'child', 'is_rep' => 0, 'br_id' => $br207, 'room_id' => 11], // Tên trẻ em mới
];

echo "\nAdding guests:\n";
foreach ($guests as $g) {
    $bgId = DB::table('booking_guests')->insertGetId([
        'booking_id' => $bookingId,
        'booking_room_id' => $g['br_id'],
        'name' => $g['name'],
        'cccd' => $g['cccd'],
        'type' => $g['type'],
        'is_representative' => $g['is_rep'],
        'status' => 'checked_in',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    
    DB::table('guests')->insert([
        'booking_id' => $bookingId,
        'booking_guest_id' => $bgId,
        'name' => $g['name'],
        'cccd' => $g['cccd'],
        'type' => $g['type'],
        'is_representative' => $g['is_rep'],
        'room_id' => $g['room_id'],
        'checkin_status' => 'checked_in',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    
    $roomNum = $g['room_id'] == 15 ? '104' : ($g['room_id'] == 7 ? '206' : '207');
    echo "  {$g['name']} - " . ($g['type'] == 'adult' ? 'Người lớn' : 'Trẻ em') . " - Room {$roomNum}\n";
}

echo "\nDone!\n";
