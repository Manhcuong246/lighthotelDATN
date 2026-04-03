<?php

// Test calculation logic
$basePrice = 1000;
$quantity = 2;
$nights = 2;
$adults = 3;
$maxAdults = 2;

// Calculate extra guests
$extraAdults = max(0, $adults - $maxAdults);
$extraAdultFeePerNight = $extraAdults * (0.4 * $basePrice);

$actualPricePerNight = $basePrice + $extraAdultFeePerNight;
$roomSubtotal = $actualPricePerNight * $quantity * $nights;
$roomSubtotalPerRoom = $actualPricePerNight * $nights;

echo "Base Price: {$basePrice}đ\n";
echo "Adults: {$adults}, Max: {$maxAdults}\n";
echo "Extra Adults: {$extraAdults}\n";
echo "Extra Fee/night: {$extraAdultFeePerNight}đ\n";
echo "Actual Price/night: {$actualPricePerNight}đ\n";
echo "Room Subtotal (total): {$roomSubtotal}đ\n";
echo "Room Subtotal per room: {$roomSubtotalPerRoom}đ\n";
echo "Expected total: " . ($roomSubtotalPerRoom * $quantity) . "đ\n";

// Check if they match
if ($roomSubtotal == ($roomSubtotalPerRoom * $quantity)) {
    echo "✅ Calculation is correct!\n";
} else {
    echo "❌ Calculation error!\n";
}
