<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('booking_logs')) {
            return;
        }

        if (! Schema::hasColumn('booking_logs', 'notes')) {
            Schema::table('booking_logs', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('new_status');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('booking_logs')) {
            return;
        }

        if (Schema::hasColumn('booking_logs', 'notes')) {
            Schema::table('booking_logs', function (Blueprint $table) {
                $table->dropColumn('notes');
            });
        }
    }
};
