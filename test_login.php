<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;
use App\Models\User;

echo "\n==================== TESTE DE LOGIN ====================\n";

$email = 'admin@hotel.local';
$password = 'Admin@123';

$user = User::with('roles')->where('email', $email)->first();

if (!$user) {
    echo "❌ Usuário não encontrado: {$email}\n";
    exit;
}

echo "✅ Usuário encontrado: {$user->email}\n";
echo "   Nome: {$user->full_name}\n";
echo "   Status: {$user->status}\n";

$roles = $user->roles->pluck('name')->toArray();
echo "   Roles: " . implode(', ', $roles) . "\n";

// Check if user has admin role
$canAccess = $user->roles()->whereIn('name', ['admin', 'staff'])->exists();
echo "\n   Pode acessar admin? " . ($canAccess ? '✅ SIM' : '❌ NÃO') . "\n";

// Test password
$passwordMatch = Hash::check($password, $user->password);
echo "\n   Teste contra a senha '{$password}':\n";
echo "   Hash correto? " . ($passwordMatch ? '✅ SIM' : '❌ NÃO') . "\n";

if ($passwordMatch && $canAccess) {
    echo "\n✅ ✅ ✅ Login deve funcionar!\n";
} else {
    echo "\n❌ Login falhará por um desses motivos:\n";
    if (!$passwordMatch) {
        echo "   - Senha incorreta\n";
    }
    if (!$canAccess) {
        echo "   - Sem permissão de admin/staff\n";
    }
}

echo "\n";
