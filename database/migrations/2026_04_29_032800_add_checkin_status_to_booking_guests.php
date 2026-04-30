<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_guests', function (Blueprint $table) {
            if (!Schema::hasColumn('booking_guests', 'checkin_status')) {
                $table->enum('checkin_status', ['pending', 'checked_in'])->default('pending')->after('status');
            }
            if (!Schema::hasColumn('booking_guests', 'is_representative')) {
                $table->boolean('is_representative')->default(0)->after('checkin_status');
            }
            if (!Schema::hasColumn('booking_guests', 'booking_room_id')) {
                $table->foreignId('booking_room_id')->nullable()->after('is_representative')->constrained('booking_rooms')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_guests', function (Blueprint $table) {
            if (Schema::hasColumn('booking_guests', 'checkin_status')) {
                $table->dropColumn('checkin_status');
            }
        });
    }
};
