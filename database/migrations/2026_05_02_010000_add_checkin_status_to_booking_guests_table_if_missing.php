<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Một số môi trường chưa chạy migration thêm checkin_status cho booking_guests,
     * trong khi code đã ghi cột này → lỗi 1054 Unknown column.
     */
    public function up(): void
    {
        if (! Schema::hasTable('booking_guests')) {
            return;
        }

        if (! Schema::hasColumn('booking_guests', 'checkin_status')) {
            Schema::table('booking_guests', function (Blueprint $table) {
                $table->enum('checkin_status', ['pending', 'checked_in', 'checked_out'])
                    ->default('pending')
                    ->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('booking_guests') && Schema::hasColumn('booking_guests', 'checkin_status')) {
            Schema::table('booking_guests', function (Blueprint $table) {
                $table->dropColumn('checkin_status');
            });
        }
    }
};
