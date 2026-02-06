<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== ADMIN LOGIN TEST ===\n\n";

// Find admin user
$admin = User::where('email', 'c@gmail.com')->with('roles')->first();

if (!$admin) {
    echo "❌ Admin user not found!\n";
    exit;
}

echo "Admin User Found:\n";
echo "- ID: {$admin->id}\n";
echo "- Email: {$admin->email}\n";
echo "- Full Name: {$admin->full_name}\n";
echo "- Roles: " . $admin->roles()->pluck('name')->implode(', ') . "\n";
echo "- Password Hash: " . substr($admin->password, 0, 20) . "...\n\n";

// Test password
$testPassword = '123456';
echo "Testing password: '{$testPassword}'\n";

$hashCheckResult = Hash::check($testPassword, $admin->password);
echo "Hash::check() result: " . ($hashCheckResult ? "✓ TRUE" : "✗ FALSE") . "\n";

if ($hashCheckResult) {
    echo "\n✓ Admin can login with password '123456'\n";
} else {
    echo "\n❌ Admin CANNOT login with password '123456'\n";
    echo "The password hash doesn't match!\n";
    echo "\nTrying to rehash the password...\n";
    $admin->password = Hash::make($testPassword);
    $admin->save();
    echo "✓ Password reset and re-hashed successfully!\n";
}

echo "\n=== END TEST ===\n";
