<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$b = DB::table('bookings')->where('id', 158)->first();
echo "Booking #158:\n";
echo "  rooms (số phòng): {$b->rooms}\n";
echo "  adults: {$b->adults}\n";
echo "  children: {$b->children}\n";
echo "  guests: {$b->guests}\n";
echo "  quantity: {$b->quantity}\n";
