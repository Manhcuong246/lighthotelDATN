<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (\App\Models\Room::whereNotNull('image')->get() as $room) {
    if (!empty($room->image)) {
        $room->image = 'room_types/dummy.png';
        $room->save();
        echo "Updated room {$room->id}\n";
    }
}

foreach (\App\Models\RoomType::whereNotNull('image')->get() as $roomType) {
    if (!empty($roomType->image)) {
        $roomType->image = 'room_types/dummy.png';
        $roomType->save();
        echo "Updated roomType {$roomType->id}\n";
    }
}
