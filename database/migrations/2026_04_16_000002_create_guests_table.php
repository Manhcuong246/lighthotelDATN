<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->integer('room_index')->default(0); // để biết khách thuộc phòng nào
            $table->string('name');
            $table->string('cccd')->nullable();
            $table->enum('checkin_status', ['pending', 'checked_in'])->default('pending');
            $table->timestamps();
            
            $table->index(['booking_id', 'room_index']);
            $table->index('checkin_status');
            $table->index('cccd');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
