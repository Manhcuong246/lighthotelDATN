<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure data is valid before altering column type to enum
        DB::table('bookings')
            ->whereNotIn('status', ['pending', 'confirmed', 'cancelled', 'completed', 'cancel_requested'])
            ->update(['status' => 'pending']);

        // Thêm 'cancel_requested' vào ENUM status của bảng bookings
        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'cancel_requested') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert về ENUM cũ (cần đảm bảo không có dữ liệu 'cancel_requested' trước khi revert)
        DB::table('bookings')
            ->whereNotIn('status', ['pending', 'confirmed', 'cancelled', 'completed'])
            ->update(['status' => 'cancelled']);

        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending'");
    }
};
