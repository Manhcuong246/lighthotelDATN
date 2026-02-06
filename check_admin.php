<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$admin = User::find(3);
echo "User 3 (Admin): {$admin->email}\n";
echo "Password hash: " . substr($admin->password, 0, 30) . "...\n";
echo "Length: " . strlen($admin->password) . "\n";

// Test if password "123456" matches (plaintext scenario)
$testPassword = '123456';
if (Hash::check($testPassword, $admin->password)) {
    echo "✓ Password '123456' matches with Hash::check()\n";
} else {
    echo "✗ Password '123456' does NOT match\n";
}

// Try plaintext comparison (old way)
if ($admin->password === $testPassword) {
    echo "✓ Password matches with plaintext comparison\n";
} else {
    echo "✗ Password does NOT match with plaintext comparison\n";
}
