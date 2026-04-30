<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Sửa booking #158: 3 phòng = 3 adults
DB::table('bookings')->where('id', 158)->update([
    'adults' => 3,
    'guests' => 3,
]);

$b = DB::table('bookings')->where('id', 158)->first();
echo "Updated #158: rooms={$b->rooms}, adults={$b->adults}, guests={$b->guests}\n";
