<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$columns = Schema::getColumnListing('bookings');
echo "Columns in bookings table:\n";
foreach ($columns as $col) {
    echo "  - $col\n";
}

if (in_array('representative_name', $columns)) {
    echo "\nrepresentative_name column EXISTS\n";
} else {
    echo "\nrepresentative_name column MISSING\n";
}

if (in_array('cccd', $columns)) {
    echo "cccd column EXISTS\n";
} else {
    echo "cccd column MISSING\n";
}
