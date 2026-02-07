<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== DETAILED BOOKING ANALYSIS ===\n\n";

// Check User 4 specifically
echo "User 4 (Mạnh Cường Nguyễn) Analysis:\n";
$user4 = User::find(4);
if ($user4) {
    echo "- Email: {$user4->email}\n";
    echo "- Full Name: {$user4->full_name}\n";
    echo "- Roles: " . $user4->roles()->pluck('name')->implode(', ') . "\n";
    echo "- Bookings count: " . $user4->bookings()->count() . "\n";

    $user4Bookings = $user4->bookings()->with('room')->get();
    if ($user4Bookings->count() > 0) {
        echo "- Bookings:\n";
        foreach ($user4Bookings as $b) {
            echo "  * ID {$b->id}: {$b->room->name} | {$b->check_in} - {$b->check_out} | Status: {$b->status}\n";
        }
    } else {
        echo "- NO BOOKINGS FOUND FOR THIS USER\n";
    }
}
echo "\n";

// Check if there might be guest bookings (before fastOrCreate feature was added)
echo "Checking for potential guest-only bookings without proper user link:\n";
$allBookings = Booking::with(['user', 'room'])->get();
foreach ($allBookings as $b) {
    if (!$b->user) {
        echo "- ORPHAN BOOKING ID {$b->id}: Room {$b->room->name} | No user linked!\n";
    }
}
echo "\n";

// Check if User 4 may have created bookings before their account existed
echo "Checking booking creation order vs user creation:\n";
$bookingWithUserDatesInfo = DB::table('bookings')
    ->select('bookings.id', 'bookings.user_id', 'bookings.created_at', 'users.created_at as user_created_at', 'users.email', 'rooms.name')
    ->leftJoin('users', 'bookings.user_id', '=', 'users.id')
    ->leftJoin('rooms', 'bookings.room_id', '=', 'rooms.id')
    ->orderBy('bookings.created_at')
    ->get();

foreach ($bookingWithUserDatesInfo as $record) {
    echo "- Booking ID {$record->id}: Created {$record->created_at} | User ID {$record->user_id} ({$record->email}) | Room: {$record->name}\n";
    if ($record->user_created_at && strtotime($record->created_at) < strtotime($record->user_created_at)) {
        echo "  ⚠️  WARNING: Booking created BEFORE user account existed!\n";
    }
}
echo "\n";

// Show all bookings with detailed info
echo "All bookings (detailed):\n";
$all = Booking::with(['user', 'room'])->orderBy('id')->get();
foreach ($all as $b) {
    $user = $b->user;
    $userName = $user ? "{$user->full_name} (ID {$user->id}, {$user->email})" : "NO USER";
    echo "- Booking {$b->id}: {$b->room->name} | Guest: {$b->guests} | {$b->check_in} to {$b->check_out} | Price: " . number_format($b->total_price, 0) . " | Status: {$b->status} | User: {$userName}\n";
}

echo "\n=== END ANALYSIS ===\n";
