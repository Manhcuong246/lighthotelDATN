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
        Schema::table('bookings', function (Blueprint $table) {
            // Thêm room_type_id để booking theo loại phòng
            $table->foreignId('room_type_id')->nullable()->after('room_id')->constrained('room_types')->onDelete('set null');
            
            // Thêm preferred_room_number cho yêu cầu đặc biệt
            $table->string('preferred_room_number', 20)->nullable()->after('room_type_id');
            
            // room_id sẽ trở thành nullable (auto-assign sau)
            $table->foreignId('room_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['room_type_id']);
            $table->dropColumn(['room_type_id', 'preferred_room_number']);
            $table->foreignId('room_id')->change();
        });
    }
};
