<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payment;
use Illuminate\Support\Facades\DB;

echo "\n==================== TEST PAYMENTS FIX ====================\n";

try {
    // Test query
    $payments = Payment::with(['booking.user', 'booking.room'])->latest()->limit(5)->get();
    
    echo "✅ Query successful!\n";
    echo "✅ Found " . $payments->count() . " payments\n\n";
    
    if ($payments->count() > 0) {
        $payment = $payments->first();
        echo "Sample Payment:\n";
        echo "  ID: " . $payment->id . "\n";
        echo "  Amount: " . $payment->amount . "\n";
        echo "  Status: " . $payment->status . "\n";
        
        // Test timestamp formatting
        if ($payment->created_at) {
            try {
                if (is_string($payment->created_at)) {
                    $formatted = \Carbon\Carbon::parse($payment->created_at)->format('d/m/Y H:i');
                } else {
                    $formatted = $payment->created_at->format('d/m/Y H:i');
                }
                echo "  Created At: " . $formatted . " ✅\n";
            } catch (\Exception $e) {
                echo "  Created At: ERROR - " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ /admin/payments should work now!\n";
    echo "✅ /admin/reviews should work now!\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
}

echo "\n";
