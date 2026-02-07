<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payment;

echo "\n==================== TEST PAYMENTS QUERY ====================\n";

try {
    $payments = Payment::with(['booking.user', 'booking.room'])->latest()->paginate(15);
    echo "✅ Query successful!\n";
    echo "✅ Found " . $payments->total() . " payments\n";
    echo "\n✅ /admin/payments should work now!\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";
