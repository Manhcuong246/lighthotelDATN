<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Thêm các cột giá vào bảng room_change_history
 * 
 * Mục đích: Lưu trữ thông tin giá cũ/mới khi đổi phòng để tracking
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('room_change_history', function (Blueprint $table) {
            // Giá cũ và mới (mỗi đêm)
            $table->decimal('old_price_per_night', 12, 2)->nullable()->after('to_room_id')
                ->comment('Giá mỗi đêm của phòng cũ');
            $table->decimal('new_price_per_night', 12, 2)->nullable()->after('old_price_per_night')
                ->comment('Giá mỗi đêm của phòng mới');
            
            // Chênh lệch giá tổng cộng
            $table->decimal('price_difference', 12, 2)->nullable()->after('new_price_per_night')
                ->comment('Chênh lệch giá (dương = tăng, âm = giảm)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_change_history', function (Blueprint $table) {
            $table->dropColumn(['old_price_per_night', 'new_price_per_night', 'price_difference']);
        });
    }
};
