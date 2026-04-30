<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CheckBooking153Cccd extends Seeder
{
    public function run(): void
    {
        echo "=== Booking #153 CCCD Check ===\n\n";
        
        $booking = DB::table('bookings')->where('id', 153)->first();
        echo "Booking ID: {$booking->id}\n";
        echo "User ID: {$booking->user_id}\n";
        echo "Guest Name: {$booking->guest_name}\n";
        echo "Guest Email: {$booking->guest_email}\n";
        echo "Guest Phone: {$booking->guest_phone}\n\n";
        
        // Check if guest has cccd
        if ($booking->user_id) {
            $user = DB::table('users')->where('id', $booking->user_id)->first();
            echo "User: {$user->full_name}\n";
            echo "User CCCD: " . ($user->cccd ?? 'NULL') . "\n";
            echo "User Identity: " . ($user->identity_card ?? 'NULL') . "\n";
            echo "User CMND: " . ($user->cmnd ?? 'NULL') . "\n\n";
        }
        
        // Check legacy guests
        $guests = DB::table('guests')->where('booking_id', 153)->get();
        echo "Legacy guests count: " . $guests->count() . "\n";
        foreach ($guests as $g) {
            echo "  Guest: {$g->name}, CCCD: " . ($g->cccd ?? 'NULL') . "\n";
        }
        
        // Check booking_guests
        $bg = DB::table('booking_guests')->where('booking_id', 153)->get();
        echo "\nBooking guests count: " . $bg->count() . "\n";
        foreach ($bg as $g) {
            echo "  Guest: {$g->name}, CCCD: " . ($g->cccd ?? 'NULL') . "\n";
        }
    }
}
