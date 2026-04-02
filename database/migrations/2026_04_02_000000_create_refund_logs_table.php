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
        Schema::create('refund_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->decimal('refund_amount', 15, 2)->default(0);
            $table->enum('refund_type', ['full', 'partial', 'none'])->default('none');
            $table->text('reason')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->index('booking_id');
            $table->index('refund_type');
            $table->index('refunded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_logs');
    }
};
