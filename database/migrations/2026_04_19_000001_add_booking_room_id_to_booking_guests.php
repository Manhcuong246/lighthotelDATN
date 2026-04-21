<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('booking_guests', function (Blueprint $table) {
            // Thêm cột booking_room_id để gán khách vào phòng cụ thể
            $table->foreignId('booking_room_id')->nullable()->after('booking_id')->constrained('booking_rooms')->onDelete('set null');
            
            // Index để query nhanh
            $table->index('booking_room_id');
            $table->index(['booking_id', 'booking_room_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_guests', function (Blueprint $table) {
            $table->dropForeign(['booking_room_id']);
            $table->dropIndex(['booking_room_id']);
            $table->dropIndex(['booking_id', 'booking_room_id']);
            $table->dropColumn('booking_room_id');
        });
    }
};
