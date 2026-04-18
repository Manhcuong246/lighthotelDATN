<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm room_id để gán khách vào phòng cụ thể khi check-in
     */
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            // room_id: phòng cụ thể được gán khi check-in (nullable vì lúc đặt chưa biết phòng nào)
            $table->foreignId('room_id')->nullable()->after('booking_id')->constrained()->onDelete('set null');
            
            // Index để query nhanh
            $table->index('room_id');
            $table->index(['booking_id', 'room_id']);
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
            $table->dropIndex(['room_id']);
            $table->dropIndex(['booking_id', 'room_id']);
            $table->dropColumn('room_id');
        });
    }
};
