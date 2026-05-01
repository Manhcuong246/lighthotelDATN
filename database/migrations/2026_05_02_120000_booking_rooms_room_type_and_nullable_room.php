<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_rooms', function (Blueprint $table) {
            $table->foreignId('room_type_id')->nullable()->after('booking_id')->constrained('room_types')->nullOnDelete();
        });

        Schema::table('booking_rooms', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
        });

        Schema::table('booking_rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('room_id')->nullable()->change();
        });

        Schema::table('booking_rooms', function (Blueprint $table) {
            $table->foreign('room_id')->references('id')->on('rooms')->nullOnDelete();
        });

        if (Schema::hasTable('booking_rooms') && Schema::hasTable('rooms')) {
            DB::statement('
                UPDATE booking_rooms br
                INNER JOIN rooms r ON r.id = br.room_id
                SET br.room_type_id = r.room_type_id
                WHERE br.room_id IS NOT NULL AND (br.room_type_id IS NULL OR br.room_type_id = 0)
            ');
        }
    }

    public function down(): void
    {
        Schema::table('booking_rooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('room_type_id');
        });
    }
};
