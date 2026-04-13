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
        Schema::table('booking_rooms', function (Blueprint $table) {
            $table->integer('adults')->default(1)->after('room_id');
            $table->integer('children_0_5')->default(0)->after('adults');
            $table->integer('children_6_11')->default(0)->after('children_0_5');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_rooms', function (Blueprint $table) {
            $table->dropColumn(['adults', 'children_0_5', 'children_6_11']);
        });
    }
};
