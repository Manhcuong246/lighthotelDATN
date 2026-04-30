<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

if (!Schema::hasColumn('bookings', 'representative_name')) {
    Schema::table('bookings', function (Blueprint $table) {
        $table->string('representative_name', 150)->nullable()->after('user_id');
    });
    echo "Added representative_name column\n";
} else {
    echo "representative_name column already exists\n";
}

// Update the booking
DB::table('bookings')
    ->where('id', 154)
    ->update([
        'representative_name' => 'Nguyễn Thu Thủy',
        'cccd' => '089765456789'
    ]);

echo "Updated booking 154\n";
