<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\BookingRoom;
use App\Models\BookingGuest;
use App\Models\Guest;

$bookingId = 154;

try {
    // Xóa cũ
    BookingGuest::where('booking_id', $bookingId)->delete();
    Guest::where('booking_id', $bookingId)->delete();
    BookingRoom::where('booking_id', $bookingId)->delete();
    
    echo "Cleared old data\n";
    
    // Tạo booking rooms
    $br1 = BookingRoom::create([
        'booking_id' => $bookingId,
        'room_id' => 15,
        'check_in' => '2026-04-30',
        'check_out' => '2026-05-01',
        'price' => 1050000,
    ]);
    
    $br2 = BookingRoom::create([
        'booking_id' => $bookingId,
        'room_id' => 7,
        'check_in' => '2026-04-30',
        'check_out' => '2026-05-01',
        'price' => 1250000,
    ]);
    
    $br3 = BookingRoom::create([
        'booking_id' => $bookingId,
        'room_id' => 11,
        'check_in' => '2026-04-30',
        'check_out' => '2026-05-01',
        'price' => 1250000,
    ]);
    
    echo "Created rooms: {$br1->id}, {$br2->id}, {$br3->id}\n";
    
    // Tạo khách
    $guestsData = [
        ['name' => 'Nguyễn Thu Thủy', 'cccd' => '089765456789', 'type' => 'adult', 'is_rep' => 1, 'br' => $br1->id],
        ['name' => 'Nguyễn Thanh Thảo', 'cccd' => null, 'type' => 'child', 'is_rep' => 0, 'br' => $br1->id],
        ['name' => 'Hoàng Văn Bách', 'cccd' => null, 'type' => 'child', 'is_rep' => 0, 'br' => $br1->id],
        ['name' => 'Nguyễn Thị Hạnh', 'cccd' => '098765456789', 'type' => 'adult', 'is_rep' => 0, 'br' => $br2->id],
        ['name' => 'Vũ Trọng Khải', 'cccd' => null, 'type' => 'child', 'is_rep' => 0, 'br' => $br2->id],
        ['name' => 'Nguyễn Quốc Việt', 'cccd' => '098765467897', 'type' => 'adult', 'is_rep' => 0, 'br' => $br3->id],
        ['name' => 'Nguyễn Thị Lan', 'cccd' => null, 'type' => 'child', 'is_rep' => 0, 'br' => $br3->id],
    ];
    
    foreach ($guestsData as $g) {
        $bg = BookingGuest::create([
            'booking_id' => $bookingId,
            'booking_room_id' => $g['br'],
            'name' => $g['name'],
            'cccd' => $g['cccd'],
            'type' => $g['type'],
            'is_representative' => $g['is_rep'],
            'status' => 'checked_in',
        ]);
        
        Guest::create([
            'booking_id' => $bookingId,
            'booking_guest_id' => $bg->id,
            'name' => $g['name'],
            'cccd' => $g['cccd'],
            'type' => $g['type'],
            'is_representative' => $g['is_rep'],
            'room_id' => $g['br'] == $br1->id ? 15 : ($g['br'] == $br2->id ? 7 : 11),
            'checkin_status' => 'checked_in',
        ]);
        
        echo "  Added: {$g['name']}\n";
    }
    
    echo "\nSuccess!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
