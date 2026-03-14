<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (\App\Models\Room::all() as $room) {
    echo "Room ID: {$room->id} - Image: {$room->image}\n";
}

foreach (\App\Models\RoomType::all() as $roomType) {
    echo "RoomType ID: {$roomType->id} - Image: {$roomType->image}\n";
}
