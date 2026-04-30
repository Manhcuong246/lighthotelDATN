<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $v = view('admin.bookings._guests_by_room')->render();
    echo "_guests_by_room OK\n";
} catch (Exception $e) {
    echo "_guests_by_room ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
