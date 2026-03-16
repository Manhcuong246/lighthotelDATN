<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== FIXING PASSWORDS ===\n\n";

$users = User::all();

foreach ($users as $user) {
    // Check if password is already hashed (bcrypt starts with $2y$)
    if (!password_get_info($user->password)['algo']) {
        // Plain text, hash it
        $user->password = Hash::make($user->password);
        $user->save();
        echo "✅ Hashed password for {$user->email}\n";
    } else {
        echo "ℹ️  Password already hashed for {$user->email}\n";
    }
}

echo "\n✅ All passwords fixed!\n";
?>
