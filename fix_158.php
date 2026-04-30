<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

DB::table('bookings')->where('id', 158)->update([
    'adults' => 2,
    'guests' => 2,
]);

echo "Updated booking #158: adults = 2\n";
