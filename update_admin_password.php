<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== CẬP NHẬT MẬT KHẨU ===\n\n";

$email = 'admin@example.com';
$newPassword = '123456';

// Tìm user
$user = User::where('email', $email)->first();

if (!$user) {
    echo "❌ Không tìm thấy user {$email}\n";
    exit(1);
}

echo "Tìm thấy user: {$user->email}\n";

// Cập nhật mật khẩu
$user->password = Hash::make($newPassword);
$user->save();

echo "\n✅ Cập nhật mật khẩu thành công!\n\n";
echo "=== THÔNG TIN ĐĂNG NHẬP ===\n";
echo "Email: {$email}\n";
echo "Mật khẩu mới: {$newPassword}\n";
echo "\nURL: http://localhost:8000/admin/login\n";
?>
