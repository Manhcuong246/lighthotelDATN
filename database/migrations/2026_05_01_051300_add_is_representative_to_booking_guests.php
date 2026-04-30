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
        if (!Schema::hasColumn('booking_guests', 'is_representative')) {
            Schema::table('booking_guests', function (Blueprint $table) {
                $table->boolean('is_representative')->default(0)->after('checkin_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('booking_guests', 'is_representative')) {
            Schema::table('booking_guests', function (Blueprint $table) {
                $table->dropColumn('is_representative');
            });
        }
    }
};
