<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n==================== FIX MISSING COLUMNS ====================\n";

// Get all tables
$tables = DB::select('SHOW TABLES');
$table_names = [];
foreach ($tables as $table) {
    foreach ($table as $name) {
        $table_names[] = $name;
    }
}

// Check for missing timestamps in each table
$tables_needing_timestamps = ['reviews', 'coupons', 'services'];

foreach ($tables_needing_timestamps as $table) {
    if (in_array($table, $table_names)) {
        // Check if table has created_at
        $columns = DB::select("SHOW COLUMNS FROM $table");
        $has_created_at = false;
        $has_updated_at = false;
        
        foreach ($columns as $col) {
            if ($col->Field === 'created_at') $has_created_at = true;
            if ($col->Field === 'updated_at') $has_updated_at = true;
        }
        
        if (!$has_created_at) {
            echo "✅ Adding created_at to $table...\n";
            DB::statement("ALTER TABLE $table ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        } else {
            echo "✓ $table has created_at\n";
        }
        
        if (!$has_updated_at) {
            echo "✅ Adding updated_at to $table...\n";
            DB::statement("ALTER TABLE $table ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        } else {
            echo "✓ $table has updated_at\n";
        }
    }
}

echo "\n✅ Kiểm tra xong!\n\n";
