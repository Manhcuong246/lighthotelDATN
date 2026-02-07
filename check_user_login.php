<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$email = 'c@gmail.com';
$password = '123456';

echo "\n==================== KIỂM TRA ĐĂNG NHẬP ====================\n";
echo "Email: {$email}\n";
echo "Mật khẩu: {$password}\n\n";

$user = User::with('roles')->where('email', $email)->first();

if (!$user) {
    echo "❌ Tài khoản không tồn tại!\n";
    echo "\n📝 Tạo tài khoản mới...\n";
    
    $user = User::create([
        'full_name' => 'User C',
        'email' => $email,
        'password' => Hash::make($password),
        'phone' => null,
        'status' => 'active',
    ]);
    
    // Gán role guest
    $guestRole = \App\Models\Role::where('name', 'guest')->first();
    if ($guestRole) {
        $user->roles()->attach($guestRole->id);
    }
    
    echo "✅ Tài khoản đã được tạo!\n";
    echo "   Email: {$user->email}\n";
    echo "   Mật khẩu: {$password}\n";
    echo "   Role: guest\n";
    echo "   Status: active\n";
} else {
    echo "✅ Tài khoản tồn tại!\n";
    echo "   Email: {$user->email}\n";
    echo "   Tên: {$user->full_name}\n";
    echo "   Status: {$user->status}\n";
    echo "   Roles: " . $user->roles->pluck('name')->join(', ') . "\n\n";
    
    // Kiểm tra mật khẩu
    $passwordMatch = Hash::check($password, $user->password);
    
    if (!$passwordMatch) {
        echo "❌ Mật khẩu không khớp!\n";
        echo "🔄 Cập nhật mật khẩu...\n";
        
        $user->password = Hash::make($password);
        $user->save();
        
        echo "✅ Mật khẩu đã được cập nhật!\n";
    } else {
        echo "✅ Mật khẩu chính xác!\n";
    }
}

// Kiểm tra lần cuối
$user->refresh();
$user->load('roles');
$finalCheck = Hash::check($password, $user->password);

echo "\n==================== KIỂM TRA CUỐI CÙNG ====================\n";
echo "✅ Email: {$user->email}\n";
echo "✅ Mật khẩu khớp: " . ($finalCheck ? 'CÓ' : 'KHÔNG') . "\n";
echo "✅ Status: {$user->status}\n";
echo "✅ Roles: " . $user->roles->pluck('name')->join(', ') . "\n";

if ($finalCheck && $user->status === 'active') {
    echo "\n✅ Bạn có thể đăng nhập bình thường!\n";
} else {
    echo "\n❌ Có vấn đề với tài khoản này.\n";
}

echo "\n";
