<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('new_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_id')->nullable()->constrained()->onDelete('set null');
            $table->date('check_in'); // ngày dự kiến
            $table->date('check_out'); // ngày dự kiến
            $table->dateTime('actual_check_in')->nullable(); // thời gian check-in thực tế
            $table->dateTime('actual_check_out')->nullable(); // thời gian check-out thực tế
            $table->enum('status', ['pending', 'checked_in', 'checked_out'])->default('pending');
            $table->decimal('total_price', 12, 2)->default(0);
            $table->string('payment_method', 50)->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
            $table->string('notes')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['check_in', 'check_out']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('new_bookings');
    }
};
