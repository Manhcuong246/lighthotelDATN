<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== USER ACCOUNT CHECK ===\n\n";

$all_users = User::with('roles')->get();

foreach ($all_users as $user) {
    echo "User {$user->id}: {$user->full_name}\n";
    echo "  Email: {$user->email}\n";
    echo "  Roles: " . $user->roles()->pluck('name')->implode(', ') . "\n";
    echo "  Status: {$user->status}\n";
    echo "  Password hash: " . substr($user->password, 0, 20) . "...\n";
    echo "  Password length: " . strlen($user->password) . " (should be 60+ if bcrypt)\n";

    // Check if password is hashed properly
    if (strlen($user->password) < 55 || strlen($user->password) > 65) {
        echo "  ⚠️  WARNING: Password may not be properly hashed! (Detected length: " . strlen($user->password) . ")\n";
    }

    // Check plaintext password storage (potential security issue)
    if (!str_starts_with($user->password, '$2')) {
        echo "  ⚠️  CRITICAL: Password not hashed with bcrypt! Looks like plaintext or wrong algo!\n";
    }

    echo "\n";
}

echo "=== INVESTIGATING PASSWORD STORAGE ===\n\n";
echo "Checking if passwords are stored as plaintext (AuthController stores plaintext):\n";
$users = User::all();
foreach ($users as $u) {
    // If password doesn't start with $2 (bcrypt), it's likely plaintext
    if (!str_starts_with($u->password, '$')) {
        echo "- User {$u->id} ({$u->email}): Password looks like PLAINTEXT\n";
    }
}

echo "\n=== END CHECK ===\n";
