<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$booking = DB::table('bookings')->where('id', 154)->first();
echo "rep_name: " . ($booking->representative_name ?? 'null') . "\n";
echo "cccd: " . ($booking->cccd ?? 'null') . "\n";
echo "user_id: " . $booking->user_id . "\n";

$user = DB::table('users')->where('id', $booking->user_id)->first();
echo "user_name: " . ($user->full_name ?? $user->name ?? 'null') . "\n";
echo "user_cccd: " . ($user->cccd ?? 'null') . "\n";
