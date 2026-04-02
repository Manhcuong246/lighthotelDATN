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
        Schema::create('damage_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->foreignId('reported_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
            $table->string('damage_type', 50); // broken_bed, ac_broken, water_leak, etc.
            $table->text('description');
            $table->enum('severity', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['reported', 'in_progress', 'resolved', 'cancelled'])->default('reported');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('repair_cost', 12, 2)->nullable();
            $table->boolean('requires_room_change')->default(false);
            $table->boolean('requires_refund')->default(false);
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->timestamps();
        });

        // Add maintenance fields to rooms table
        Schema::table('rooms', function (Blueprint $table) {
            $table->text('maintenance_note')->nullable()->after('status');
            $table->timestamp('maintenance_since')->nullable()->after('maintenance_note');
            $table->foreignId('damage_report_id')->nullable()->constrained('damage_reports')->onDelete('set null')->after('maintenance_since');
        });

        // Add room_change_history table
        Schema::create('room_change_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_room_id')->constrained('rooms')->onDelete('cascade');
            $table->foreignId('to_room_id')->constrained('rooms')->onDelete('cascade');
            $table->foreignId('damage_report_id')->nullable()->constrained('damage_reports')->onDelete('set null');
            $table->text('reason');
            $table->foreignId('changed_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('changed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_change_history');
        Schema::dropIfExists('damage_reports');

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['maintenance_note', 'maintenance_since', 'damage_report_id']);
        });
    }
};
