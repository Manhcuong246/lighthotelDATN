<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Đổi status từ ENUM sang VARCHAR để linh hoạt hơn
        DB::statement("ALTER TABLE payments MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Quay lại ENUM
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'paid', 'failed') DEFAULT 'pending'");
    }
};
