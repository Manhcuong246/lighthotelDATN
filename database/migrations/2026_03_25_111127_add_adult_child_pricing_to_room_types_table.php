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
        Schema::table('room_types', function (Blueprint $table) {
            if (!Schema::hasColumn('room_types', 'adult_capacity')) {
                $table->integer('adult_capacity')->default(2)->after('capacity');
            }
            if (!Schema::hasColumn('room_types', 'child_capacity')) {
                $table->integer('child_capacity')->default(1)->after('adult_capacity');
            }
            if (!Schema::hasColumn('room_types', 'adult_price')) {
                $table->decimal('adult_price', 10, 2)->default(0)->after('price');
            }
            if (!Schema::hasColumn('room_types', 'child_price')) {
                $table->decimal('child_price', 10, 2)->default(0)->after('adult_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn(['adult_capacity', 'child_capacity', 'adult_price', 'child_price']);
        });
    }
};
