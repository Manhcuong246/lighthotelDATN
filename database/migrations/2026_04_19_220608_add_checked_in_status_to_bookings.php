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
        // Thêm 'checked_in' và 'checked_out' vào ENUM status của bảng bookings
        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'cancel_requested', 'checked_in', 'checked_out') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert về ENUM cũ (cần đảm bảo không có dữ liệu 'checked_in' hoặc 'checked_out' trước khi revert)
        DB::table('bookings')
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->update(['status' => 'confirmed']);

        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'cancel_requested') DEFAULT 'pending'");
    }
};
