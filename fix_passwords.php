<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== FIXING PLAINTEXT PASSWORDS ===\n\n";

$users = User::all();
$updated = 0;

foreach ($users as $user) {
    // Check if password is plaintext (not bcrypt hash)
    if (!str_starts_with($user->password, '$2')) {
        $oldPassword = $user->password;
        $user->password = Hash::make($oldPassword);
        $user->save();
        echo "✓ Fixed User {$user->id} ({$user->email}): Password '{$oldPassword}' -> hashed\n";
        $updated++;
    } else {
        echo "✓ User {$user->id} ({$user->email}): Already hashed\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Total users updated: {$updated}\n";
echo "All passwords are now properly bcrypt-hashed!\n";
