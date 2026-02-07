<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "\n==================== FIX PASSWORD HASHING ====================\n";

$users = User::all();

if ($users->isEmpty()) {
    echo "❌ Không có người dùng nào!\n";
    exit;
}

echo "✅ Tìm thấy " . $users->count() . " người dùng\n\n";

foreach ($users as $user) {
    echo "Kiểm tra: {$user->email}\n";
    
    $password = $user->password;
    
    // Check if it's already a bcrypt hash (starts with $2y$ or $2b$ or $2a$)
    if (preg_match('/^\$2[aby]\$/', $password)) {
        echo "   ✅ Đã là Bcrypt hash\n";
    } else {
        echo "   ❌ KHÔNG PHẢI Bcrypt hash! Cập nhật...\n";
        // Rehash with bcrypt
        $user->password = Hash::make($password);
        $user->save();
        echo "   ✅ Đã cập nhật thành công\n";
    }
}

echo "\n==================== KIỂM TRA LẠI ====================\n";

$users = User::all();
$allGood = true;

foreach ($users as $user) {
    if (!preg_match('/^\$2[aby]\$/', $user->password)) {
        echo "❌ {$user->email} - VẪNKHÔNG PHẢI Bcrypt!\n";
        $allGood = false;
    }
}

if ($allGood) {
    echo "✅ TẤT CẢ passwords đều từ hàm bcrypt!\n";
    echo "\n✅ Bạn có thể đăng nhập bình thường!\n";
} else {
    echo "\n❌ Vẫn có vấn đề!\n";
}

echo "\n";
