<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $dupGroups = DB::table('reviews')
            ->selectRaw('user_id, room_id, MIN(id) as keep_id')
            ->groupBy('user_id', 'room_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($dupGroups as $row) {
            DB::table('reviews')
                ->where('user_id', $row->user_id)
                ->where('room_id', $row->room_id)
                ->where('id', '!=', $row->keep_id)
                ->delete();
        }

        Schema::table('reviews', function (Blueprint $table) {
            $table->unique(['user_id', 'room_id'], 'reviews_user_id_room_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_user_id_room_id_unique');
        });
    }
};
