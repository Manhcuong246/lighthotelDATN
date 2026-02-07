<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$user = User::with('roles')->where('email', 'c@gmail.com')->first();

echo "\n==================== THÔNG TIN TÀI KHOẢN C@GMAIL.COM ====================\n";

if (!$user) {
    echo "❌ Tài khoản không tồn tại!\n";
} else {
    echo "✅ Email: {$user->email}\n";
    echo "✅ Tên: {$user->full_name}\n";
    echo "✅ Status: {$user->status}\n";
    echo "✅ Roles: " . ($user->roles->isEmpty() ? 'KHÔNG CÓ' : $user->roles->pluck('name')->join(', ')) . "\n";
    echo "✅ Mật khẩu: 123456\n";
}

echo "\n";
