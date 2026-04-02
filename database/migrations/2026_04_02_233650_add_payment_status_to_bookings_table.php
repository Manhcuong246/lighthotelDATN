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
        Schema::table('bookings', function (Blueprint $table) {
            // Add missing columns
            if (!Schema::hasColumn('bookings', 'payment_status')) {
                $table->string('payment_status', 20)->default('pending');
            }
            
            if (!Schema::hasColumn('bookings', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable();
            }
            
            if (!Schema::hasColumn('bookings', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
            }
            
            if (!Schema::hasColumn('bookings', 'check_in_date')) {
                $table->datetime('check_in_date')->nullable();
            }
            
            if (!Schema::hasColumn('bookings', 'check_out_date')) {
                $table->datetime('check_out_date')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'cancellation_reason', 
                'cancelled_at',
                'check_in_date',
                'check_out_date'
            ]);
        });
    }
};
