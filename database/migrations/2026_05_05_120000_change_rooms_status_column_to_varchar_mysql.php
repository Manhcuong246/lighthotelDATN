<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MySQL ENUM thiếu giá trị (vd. không có 'booked') gây Warning 1265 / truncate khi UPDATE.
     * Dùng VARCHAR để khớp RoomAdminController và các luồng gán phòng (booked / occupied / …).
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

        DB::statement("ALTER TABLE `rooms` MODIFY COLUMN `status` VARCHAR(32) NOT NULL DEFAULT 'available'");
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

        DB::statement("ALTER TABLE `rooms` MODIFY COLUMN `status` ENUM('available', 'booked', 'maintenance', 'occupied', 'cleaning') NOT NULL DEFAULT 'available'");
    }
};
