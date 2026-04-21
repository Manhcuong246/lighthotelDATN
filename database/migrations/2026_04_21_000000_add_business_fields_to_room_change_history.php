<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Thêm cột nghiệp vụ đổi phòng vào room_change_history
 * 
 * - change_type: loại đổi phòng (same_grade, upgrade, downgrade, emergency)
 * - remaining_nights: số đêm còn lại tại thời điểm đổi
 * - old_room_status: trạng thái phòng cũ trước khi đổi (để hoàn tác)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_change_history', function (Blueprint $table) {
            $table->string('change_type', 20)->default('same_grade')->after('price_difference')
                ->comment('same_grade, upgrade, downgrade, emergency');
            $table->integer('remaining_nights')->default(0)->after('change_type')
                ->comment('Số đêm còn lại tại thời điểm đổi phòng');
            $table->string('old_room_status', 30)->nullable()->after('remaining_nights')
                ->comment('Trạng thái phòng cũ trước khi đổi (để hoàn tác)');
        });
    }

    public function down(): void
    {
        Schema::table('room_change_history', function (Blueprint $table) {
            $table->dropColumn(['change_type', 'remaining_nights', 'old_room_status']);
        });
    }
};
