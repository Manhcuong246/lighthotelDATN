<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Warning 1265 (data truncated) khi UPDATE rooms.status = 'booked' xảy ra nếu cột vẫn là ENUM
     * không có giá trị đó (DB cũ / tay đổi schema). Đổi sang VARCHAR một lần.
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

        $row = DB::selectOne(
            'SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['rooms', 'status']
        );

        if (! $row || ! isset($row->DATA_TYPE)) {
            return;
        }

        $dataType = strtolower((string) $row->DATA_TYPE);
        if ($dataType === 'enum' || $dataType === 'set') {
            DB::statement("ALTER TABLE `rooms` MODIFY COLUMN `status` VARCHAR(64) NOT NULL DEFAULT 'available'");
        }
    }

    public function down(): void
    {
        // Không rollback sang ENUM — tránh tái hiện lỗi 1265 trên DB đã có giá trị tự do.
    }
};
