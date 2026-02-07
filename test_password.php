<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;
use App\Models\User;

$user = User::find(1);

if ($user) {
    echo "\n==================== TESTE DE SENHA ====================\n";
    echo "Email: {$user->email}\n";
    echo "\nTestando várias senhas:\n\n";
    
    $passwords_to_test = [
        'Admin@123',
        'admin
',
        'admin123',
        'password',
        '123456',
    ];
    
    foreach ($passwords_to_test as $pwd) {
        $trimmed = trim($pwd);
        $match = Hash::check($trimmed, $user->password) ? '✅ CORRETO' : '❌ INCORRETO';
        echo "Senha: '{$trimmed}' => {$match}\n";
    }
    
    echo "\n==================== GERAR NOVA SENHA ====================\n";
    echo "Gerando novo hash para 'Admin@123'...\n";
    $newHash = Hash::make('Admin@123');
    echo "Novo Hash: {$newHash}\n";
    
    // Verify the new hash
    $verify = Hash::check('Admin@123', $newHash) ? '✅ Verifica OK' : '❌ Falha na verificação';
    echo "Verificação: {$verify}\n";
    
    // Update the user
    $user->password = $newHash;
    $user->save();
    echo "\n✅ Senha atualizada com sucesso!\n";
}

echo "\n";
