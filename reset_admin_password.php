<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

echo "=== RESETTING ADMIN PASSWORD ===\n\n";

$admin = User::find(3);
if ($admin) {
    $newPassword = 'admin123';
    $admin->password = Hash::make($newPassword);
    $admin->save();

    echo "✓ Admin password reset successfully!\n\n";
    echo "Login credentials:\n";
    echo "- Email: {$admin->email}\n";
    echo "- Password: {$newPassword}\n\n";
}
