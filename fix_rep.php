<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

DB::table('bookings')
    ->where('id', 154)
    ->update([
        'representative_name' => 'Nguyễn Thu Thủy',
        'cccd' => '089765456789'
    ]);

$booking = DB::table('bookings')->where('id', 154)->first();
echo "Updated - rep_name: " . ($booking->representative_name ?? 'null') . "\n";
echo "Updated - cccd: " . ($booking->cccd ?? 'null') . "\n";
