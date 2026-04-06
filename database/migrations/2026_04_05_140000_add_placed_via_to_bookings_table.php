<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bookings', 'placed_via')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->string('placed_via', 32)->nullable()->after('payment_method');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'placed_via')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('placed_via');
            });
        }
    }
};
