<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->unsignedInteger('standard_capacity')
                ->nullable()
                ->after('capacity');
        });

        // Backfill: default standard = min(3, capacity) for existing rows
        DB::table('room_types')
            ->whereNull('standard_capacity')
            ->update([
                'standard_capacity' => DB::raw('LEAST(3, capacity)'),
            ]);

        Schema::table('room_types', function (Blueprint $table) {
            $table->unsignedInteger('standard_capacity')
                ->nullable(false)
                ->default(3)
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn('standard_capacity');
        });
    }
};

