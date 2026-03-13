<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== DANH SÁCH TÀI KHOẢN ADMIN ===\n\n";

// Lấy tất cả user có role admin
$adminUsers = User::whereHas('roles', function($q) {
    $q->where('name', 'admin');
})->get();

if ($adminUsers->isEmpty()) {
    echo "❌ Không có tài khoản admin nào\n";
    exit(1);
}

echo "Tìm thấy " . $adminUsers->count() . " tài khoản admin:\n\n";

foreach ($adminUsers as $index => $user) {
    echo ($index + 1) . ". Email: {$user->email}\n";
    echo "   Full Name: {$user->full_name}\n";
    echo "   Status: {$user->status}\n";
    echo "   Password (Hash): {$user->password}\n";
    echo "\n";
}

echo "⚠️ Lưu ý: Password được mã hóa (hash), không thể xem mật khẩu gốc.\n";
echo "Nếu cần reset mật khẩu, hãy yêu cầu tôi cập nhật.\n";
?>
