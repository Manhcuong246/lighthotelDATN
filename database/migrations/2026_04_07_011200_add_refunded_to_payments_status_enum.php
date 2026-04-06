<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        DB::statement("ALTER TABLE `payments` MODIFY `status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        DB::statement("ALTER TABLE `payments` MODIFY `status` ENUM('pending', 'paid', 'failed') NOT NULL");
    }
};
