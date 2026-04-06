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
        if (! Schema::hasTable('refund_logs')) {
            return;
        }

        if (! Schema::hasColumn('refund_logs', 'processed_by')) {
            Schema::table('refund_logs', function (Blueprint $table) {
                $table->foreignId('processed_by')
                    ->nullable()
                    ->after('reason')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('refund_logs', 'refunded_at')) {
            Schema::table('refund_logs', function (Blueprint $table) {
                $table->timestamp('refunded_at')->nullable()->after('processed_by');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('refund_logs')) {
            return;
        }

        if (Schema::hasColumn('refund_logs', 'refunded_at')) {
            Schema::table('refund_logs', function (Blueprint $table) {
                $table->dropColumn('refunded_at');
            });
        }

        if (Schema::hasColumn('refund_logs', 'processed_by')) {
            Schema::table('refund_logs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('processed_by');
            });
        }
    }
};
