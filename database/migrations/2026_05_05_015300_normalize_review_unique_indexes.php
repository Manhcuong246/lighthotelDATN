<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reviews')) {
            return;
        }

        // Best effort: remove legacy unique constraints that can block
        // "one review per room per booking" behavior.
        $this->dropUniqueIfExists('reviews', 'reviews_user_id_room_id_unique');
        $this->dropUniqueIfExists('reviews', 'reviews_booking_id_unique');

        // Ensure target unique key exists.
        if (! $this->indexExists('reviews', 'reviews_booking_id_room_id_unique')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->unique(['booking_id', 'room_id'], 'reviews_booking_id_room_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('reviews')) {
            return;
        }

        $this->dropUniqueIfExists('reviews', 'reviews_booking_id_room_id_unique');
    }

    private function dropUniqueIfExists(string $table, string $indexName): void
    {
        try {
            DB::statement(sprintf('ALTER TABLE `%s` DROP INDEX `%s`', $table, $indexName));
        } catch (\Throwable) {
            // index does not exist or driver does not support this syntax
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $result = DB::selectOne(
                'SELECT COUNT(1) AS c FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
                [$table, $indexName]
            );

            return ((int) ($result->c ?? 0)) > 0;
        } catch (\Throwable) {
            return false;
        }
    }
};

