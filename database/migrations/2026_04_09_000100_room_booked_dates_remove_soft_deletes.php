<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * room_booked_dates là “khóa slot” theo ngày — soft delete vẫn chiếm unique (room_id, booked_date)
     * trong khi Eloquent mặc định không thấy bản ghi đã xóa → dễ lỗi 1062 hoặc hiển thị còn trống.
     */
    public function up(): void
    {
        if (! Schema::hasTable('room_booked_dates')) {
            return;
        }

        if (Schema::hasColumn('room_booked_dates', 'deleted_at')) {
            DB::table('room_booked_dates')->whereNotNull('deleted_at')->delete();

            Schema::table('room_booked_dates', function (Blueprint $table) {
                try {
                    $table->dropIndex(['room_id', 'booked_date']);
                } catch (\Throwable) {
                    //
                }
                $table->dropSoftDeletes();
            });
        }

        $dbName = Schema::getConnection()->getDatabaseName();
        $hasUnique = DB::table('information_schema.statistics')
            ->where('table_schema', $dbName)
            ->where('table_name', 'room_booked_dates')
            ->where('index_name', 'room_booked_dates_room_id_booked_date_unique')
            ->exists();

        if (! $hasUnique) {
            try {
                DB::statement('ALTER TABLE `room_booked_dates` ADD UNIQUE KEY `room_booked_dates_room_id_booked_date_unique` (`room_id`, `booked_date`)');
            } catch (\Throwable) {
                //
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('room_booked_dates')) {
            return;
        }

        Schema::table('room_booked_dates', function (Blueprint $table) {
            try {
                $table->dropUnique('room_booked_dates_room_id_booked_date_unique');
            } catch (\Throwable) {
                try {
                    $table->dropUnique(['room_id', 'booked_date']);
                } catch (\Throwable) {
                    //
                }
            }
        });

        if (! Schema::hasColumn('room_booked_dates', 'deleted_at')) {
            Schema::table('room_booked_dates', function (Blueprint $table) {
                $table->softDeletes();
                $table->index(['room_id', 'booked_date']);
            });
        }
    }
};
