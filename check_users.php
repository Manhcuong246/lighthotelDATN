<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$users = User::with('roles')->get();

echo "\n==================== DANH SÁCH NGƯỜI DÙNG ====================\n";

if ($users->isEmpty()) {
    echo "❌ Không có người dùng nào trong cơ sở dữ liệu!\n";
} else {
    foreach ($users as $user) {
        $roles = $user->roles->pluck('name')->join(', ') ?: 'không có role';
        echo "\n👤 ID: {$user->id}";
        echo "\n   Email: {$user->email}";
        echo "\n   Tên: {$user->full_name}";
        echo "\n   Trạng thái: {$user->status}";
        echo "\n   Vai trò: {$roles}";
        echo "\n   Password Hash: " . substr($user->password, 0, 50) . "...";
        echo "\n";
    }
}

echo "\n==================== DANH SÁCH ROLES ====================\n";
$roles = \App\Models\Role::all();
if ($roles->isEmpty()) {
    echo "❌ Không có role nào!\n";
} else {
    foreach ($roles as $role) {
        echo "- {$role->id}: {$role->name}\n";
    }
}

echo "\n";
