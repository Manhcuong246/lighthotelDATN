<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('booking_guests')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE booking_guests MODIFY COLUMN type VARCHAR(32) NOT NULL DEFAULT 'adult'");
        }

        DB::table('booking_guests')->where('type', 'child')->update(['type' => 'child_0_5']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('booking_guests')) {
            return;
        }

        DB::table('booking_guests')->where('type', 'child_6_11')->update(['type' => 'child']);
        DB::table('booking_guests')->where('type', 'child_0_5')->update(['type' => 'child']);

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE booking_guests MODIFY COLUMN type ENUM('adult','child') NOT NULL DEFAULT 'adult'");
        }
    }
};
