<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "\n==================== CREDENCIAIS DO ADMIN ====================\n";
echo "📧 Email:    admin@hotel.local\n";
echo "🔐 Senha:    Admin@123\n";
echo "🔑 Usuário:  Admin User\n";
echo "👥 Função:   admin\n";
echo "📊 Status:   active\n";

echo "\n==================== CHECKLIST ====================\n";

$user = User::with('roles')->where('email', 'admin@hotel.local')->first();

$checks = [
    'Usuário existe' => $user !== null,
    'Email correto' => $user?->email === 'admin@hotel.local',
    'Status ativo' => $user?->status === 'active',
    'Tem role admin' => $user?->roles()->where('name', 'admin')->exists(),
    'Senha pode ser verificada' => \Illuminate\Support\Facades\Hash::check('Admin@123', $user?->password ?? ''),
];

foreach ($checks as $check => $result) {
    $icon = $result ? '✅' : '❌';
    echo "{$icon} {$check}\n";
}

if (array_sum($checks) === count($checks)) {
    echo "\n✅ TUDO PRONTO! Você pode fazer login agora.\n";
    echo "\n🌐 Acesse: http://localhost/admin/login\n";
    echo "📧 E-mail: admin@hotel.local\n";
    echo "🔐 Senha:  Admin@123\n";
} else {
    echo "\n❌ Há problemas. Verifique acima.\n";
}

echo "\n";
