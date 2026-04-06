<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('booking_surcharges')) {
            return;
        }

        Schema::table('booking_surcharges', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_surcharges', 'service_id')) {
                $table->foreignId('service_id')
                    ->nullable()
                    ->after('booking_id')
                    ->constrained('services')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('booking_surcharges', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1)->after('reason');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('booking_surcharges')) {
            return;
        }

        Schema::table('booking_surcharges', function (Blueprint $table) {
            if (Schema::hasColumn('booking_surcharges', 'quantity')) {
                $table->dropColumn('quantity');
            }
            if (Schema::hasColumn('booking_surcharges', 'service_id')) {
                $table->dropConstrainedForeignId('service_id');
            }
        });
    }
};
