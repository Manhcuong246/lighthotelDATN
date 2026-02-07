<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;

$email = 'c@gmail.com';

echo "\n==================== CẬP NHẬT QUYỀN ADMIN ====================\n";

$user = User::with('roles')->where('email', $email)->first();

if (!$user) {
    echo "❌ Tài khoản không tồn tại!\n";
    exit;
}

echo "✅ Tài khoản tìm thấy: {$user->email}\n";
echo "   Tên: {$user->full_name}\n";
echo "   Roles hiện tại: " . $user->roles->pluck('name')->join(', ') . "\n\n";

// Xóa các role cũ
$user->roles()->detach();
echo "🔄 Đã xóa các role cũ...\n";

// Thêm role admin
$adminRole = Role::where('name', 'admin')->first();
if ($adminRole) {
    $user->roles()->attach($adminRole->id);
    echo "✅ Đã thêm role admin!\n";
} else {
    echo "❌ Role admin không tồn tại!\n";
    exit;
}

// Kiểm tra lại
$user->refresh();
$user->load('roles');

echo "\n==================== KIỂM TRA CUỐI CÙNG ====================\n";
echo "✅ Email: {$user->email}\n";
echo "✅ Tên: {$user->full_name}\n";
echo "✅ Roles: " . $user->roles->pluck('name')->join(', ') . "\n";
echo "✅ Status: {$user->status}\n";
echo "✅ Mật khẩu: 123456\n";

echo "\n✅ Tài khoản admin đã được tạo/cập nhật!\n";
echo "\n🌐 Đăng nhập admin: http://localhost/admin/login\n";
echo "📧 Email: c@gmail.com\n";
echo "🔐 Mật khẩu: 123456\n";
echo "\n";
