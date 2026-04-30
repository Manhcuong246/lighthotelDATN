<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$bookingId = 154;

// Xóa cũ
DB::table('booking_guests')->where('booking_id', $bookingId)->delete();

echo "Adding guests...\n";

// Lấy booking room IDs
$br104 = DB::table('booking_rooms')->where('booking_id', $bookingId)->where('room_id', 15)->first();
$br206 = DB::table('booking_rooms')->where('booking_id', $bookingId)->where('room_id', 7)->first();
$br207 = DB::table('booking_rooms')->where('booking_id', $bookingId)->where('room_id', 11)->first();

if (!$br104 || !$br206 || !$br207) {
    echo "ERROR: Missing booking rooms!\n";
    exit;
}

echo "br104={$br104->id}, br206={$br206->id}, br207={$br207->id}\n";

$now = now();
$guests = [
    ['name' => 'Nguyễn Thu Thủy', 'cccd' => '089765456789', 'type' => 'adult', 'is_rep' => 1, 'br_id' => $br104->id],
    ['name' => 'Nguyễn Thanh Thảo', 'cccd' => null, 'type' => 'child', 'is_rep' => 0, 'br_id' => $br104->id],
    ['name' => 'Hoàng Văn Bách', 'cccd' => null, 'type' => 'child', 'is_rep' => 0, 'br_id' => $br104->id],
    ['name' => 'Nguyễn Thị Hạnh', 'cccd' => '098765456789', 'type' => 'adult', 'is_rep' => 0, 'br_id' => $br206->id],
    ['name' => 'Vũ Trọng Khải', 'cccd' => null, 'type' => 'child', 'is_rep' => 0, 'br_id' => $br206->id],
    ['name' => 'Nguyễn Quốc Việt', 'cccd' => '098765467897', 'type' => 'adult', 'is_rep' => 0, 'br_id' => $br207->id],
    ['name' => 'Nguyễn Thị Lan', 'cccd' => null, 'type' => 'child', 'is_rep' => 0, 'br_id' => $br207->id],
];

foreach ($guests as $g) {
    try {
        DB::table('booking_guests')->insert([
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
        echo "  OK: {$g['name']}\n";
    } catch (Exception $e) {
        echo "  ERROR: {$g['name']} - " . $e->getMessage() . "\n";
    }
}

echo "\nDone!\n";
