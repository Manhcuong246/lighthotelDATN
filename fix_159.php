<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Sửa booking #159: 3 phòng = 3 adults
DB::table('bookings')->where('id', 159)->update([
    'adults' => 3,
    'guests' => 3,
]);

$b = DB::table('bookings')->where('id', 159)->first();
echo "Updated #159: adults={$b->adults}, guests={$b->guests}, quantity={$b->quantity}\n";
