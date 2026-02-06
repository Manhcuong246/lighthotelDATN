<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "\n=== ADMIN ACCOUNT CREDENTIALS ===\n\n";

$admin = User::find(3);
if ($admin) {
    echo "Email: {$admin->email}\n";
    echo "Password (default): 123456\n";
    echo "Roles: " . $admin->roles()->pluck('name')->implode(', ') . "\n";
    echo "Status: {$admin->status}\n\n";

    echo "Hãy thử đăng nhập lại với:\n";
    echo "- Email: {$admin->email}\n";
    echo "- Password: 123456\n\n";

    // Show all admin/staff users
    echo "All admin/staff users:\n";
    $admins = User::whereHas('roles', function($q) {
        $q->whereIn('name', ['admin', 'staff']);
    })->with('roles')->get();

    foreach ($admins as $u) {
        echo "- {$u->full_name} ({$u->email}) [Role: " . $u->roles()->pluck('name')->implode(', ') . "]\n";
    }
}
