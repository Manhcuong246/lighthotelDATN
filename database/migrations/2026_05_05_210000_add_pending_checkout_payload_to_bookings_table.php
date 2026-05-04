<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'pending_checkout_payload')) {
                if (Schema::hasColumn('bookings', 'notes')) {
                    $table->json('pending_checkout_payload')->nullable()->after('notes');
                } else {
                    $table->json('pending_checkout_payload')->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'pending_checkout_payload')) {
                $table->dropColumn('pending_checkout_payload');
            }
        });
    }
};
