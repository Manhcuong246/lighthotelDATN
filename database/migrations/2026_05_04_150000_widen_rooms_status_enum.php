<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Một số DB chỉ có ENUM available/maintenance hoặc thiếu booked/cleaning/occupied —
     * gây SQLSTATE 1265 khi code ghi booked | occupied | cleaning.
     */
    public function up(): void
    {
        if (! Schema::hasTable('rooms')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE `rooms` MODIFY COLUMN `status` ENUM('available', 'booked', 'maintenance', 'occupied', 'cleaning') NOT NULL DEFAULT 'available'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('rooms')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::table('rooms')->whereIn('status', ['occupied', 'cleaning'])->update(['status' => 'booked']);

        DB::statement("ALTER TABLE `rooms` MODIFY COLUMN `status` ENUM('available', 'booked', 'maintenance') NOT NULL DEFAULT 'available'");
    }
};
