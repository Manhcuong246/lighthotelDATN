<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sửa SQLSTATE[01000] / 1265 «Data truncated for column 'status'» khi UPDATE rooms.status = 'booked'.
     * Nguyên nhân thường gặp: cột còn là ENUM/SET không có đủ giá trị, hoặc VARCHAR quá ngắn so với dữ liệu thực tế.
     * ALTER idempotent: an toàn chạy lại trên MySQL/MariaDB.
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

        DB::statement("ALTER TABLE `rooms` MODIFY COLUMN `status` VARCHAR(64) NOT NULL DEFAULT 'available'");
    }

    public function down(): void
    {
        // Không thu hẹp lại ENUM — tránh tái hiện 1265 trên DB đã có giá trị tự do.
    }
};
