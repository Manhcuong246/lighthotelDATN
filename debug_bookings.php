<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== BOOKING DIAGNOSTICS ===\n\n";

// 1. Count total bookings
$totalBookings = Booking::count();
echo "1. Total bookings: {$totalBookings}\n\n";

// 2. Count bookings with NULL user_id
$nullUserBookings = Booking::whereNull('user_id')->count();
echo "2. Bookings with NULL user_id: {$nullUserBookings}\n";
if ($nullUserBookings > 0) {
    $bookings = Booking::whereNull('user_id')->with('room')->get();
    foreach ($bookings as $b) {
        echo "   - Booking ID {$b->id}: Room {$b->room->name}, Check-in: {$b->check_in}, Status: {$b->status}\n";
    }
}
echo "\n";

// 3. Bookings grouped by user
echo "3. Bookings by user:\n";
$bookingsByUser = DB::table('bookings')
    ->select('user_id', DB::raw('COUNT(*) as count'))
    ->groupBy('user_id')
    ->orderBy('count', 'desc')
    ->get();

foreach ($bookingsByUser as $record) {
    $user = User::find($record->user_id);
    $userName = $user ? "{$user->full_name} ({$user->email})" : 'NULL';
    echo "   - User ID {$record->user_id} ({$userName}): {$record->count} bookings\n";
}
echo "\n";

// 4. Count active users
$activeUsers = User::count();
echo "4. Total users: {$activeUsers}\n";
echo "5. Users with roles:\n";
$usersWithRole = DB::table('users')
    ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
    ->select('users.id', 'users.full_name', 'users.email', 'roles.name')
    ->join('roles', 'user_roles.role_id', '=', 'roles.id')
    ->orderBy('users.id')
    ->get();

foreach ($usersWithRole as $u) {
    $bookingCount = Booking::where('user_id', $u->id)->count();
    echo "   - User {$u->id}: {$u->full_name} ({$u->email}) [Role: {$u->name}] - {$bookingCount} bookings\n";
}
echo "\n";

// 6. List all recent bookings with user info
echo "6. Last 10 bookings:\n";
$recent = Booking::with(['user', 'room'])->latest()->limit(10)->get();
foreach ($recent as $b) {
    $userName = $b->user ? "{$b->user->full_name} ({$b->user->email})" : 'NO USER';
    echo "   - ID {$b->id}: {$b->room->name} | User: {$userName} | Status: {$b->status} | Check-in: {$b->check_in}\n";
}

echo "\n=== END DIAGNOSTICS ===\n";
