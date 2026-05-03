<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reviews')) {
            return;
        }

        Schema::table('reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('reviews', 'booking_id')) {
                $table->foreignId('booking_id')
                    ->nullable()
                    ->after('room_id')
                    ->constrained('bookings')
                    ->nullOnDelete();
            }
        });

        Schema::table('reviews', function (Blueprint $table) {
            try {
                $table->unique(['booking_id', 'room_id'], 'reviews_booking_id_room_id_unique');
            } catch (\Throwable) {
                // Đã có index (driver / môi trường khác)
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('reviews')) {
            return;
        }

        Schema::table('reviews', function (Blueprint $table) {
            try {
                $table->dropUnique('reviews_booking_id_room_id_unique');
            } catch (\Throwable) {
                try {
                    $table->dropUnique(['booking_id', 'room_id']);
                } catch (\Throwable) {
                    //
                }
            }
        });

        Schema::table('reviews', function (Blueprint $table) {
            if (Schema::hasColumn('reviews', 'booking_id')) {
                $table->dropForeign(['booking_id']);
                $table->dropColumn('booking_id');
            }
        });
    }
};
