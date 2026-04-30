<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
/** @var \Illuminate\Contracts\Console\Kernel $kernel */
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "=== TEST VNPay Email Booking ===\n\n";

// Get test data
$room = App\Models\Room::first();
$user = App\Models\User::where('email', '!=', '')->first();

if (!$room || !$user) {
    echo "ERROR: No room or user found!\n";
    exit(1);
}

echo "✓ Room: {$room->room_number} (ID: {$room->id})\n";
echo "✓ User: {$user->email}\n\n";

// Test booking creation
try {
    echo "Creating test booking...\n";

    $checkIn = now()->addDays(5)->format('Y-m-d');
    $checkOut = now()->addDays(8)->format('Y-m-d');

    $booking = App\Models\Booking::create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'guests' => 2,
        'adults' => 2,
        'children' => 0,
        'total_price' => 1500000,
        'status' => 'pending',
        'payment_status' => 'pending',
        'payment_method' => 'vnpay',
        'placed_via' => 'admin',
    ]);

    echo "✓ Booking created: #{$booking->id}\n";
    echo "  - Check-in: {$checkIn}\n";
    echo "  - Check-out: {$checkOut}\n";
    echo "  - Total: " . number_format($booking->total_price) . "đ\n";
    echo "  - Payment: VNPay (pending)\n\n";

    // Test email sending
    echo "Testing email send...\n";

    $controller = new App\Http\Controllers\Admin\BookingAdminController();
    $reflection = new ReflectionMethod($controller, 'sendVnPayPaymentEmail');
    /** @phpstan-ignore-next-line - Intentionally using deprecated method for testing */
    $reflection->setAccessible(true);

    $reflection->invoke($controller, $booking, 2, 0, 0);

    echo "✓ Email function executed!\n";
    echo "  - Check storage/logs/laravel.log for details\n\n";

    // Show VNPay link
    $reflection2 = new ReflectionMethod($controller, 'signedVnPayEntryUrl');
    /** @phpstan-ignore-next-line - Intentionally using deprecated method for testing */
    $reflection2->setAccessible(true);
    $vnpayUrl = $reflection2->invoke($controller, $booking);

    echo "VNPay Payment Link:\n";
    echo "{$vnpayUrl}\n\n";

    echo "=== TEST COMPLETE ===\n";
    echo "\nTo verify:\n";
    echo "1. Check email inbox: {$user->email}\n";
    echo "2. Check logs: storage/logs/laravel.log\n";
    echo "3. Visit the VNPay link above\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
