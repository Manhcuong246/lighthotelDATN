<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_payment_intents', function (Blueprint $table) {
            $table->id();
            $table->json('payload');
            $table->unsignedBigInteger('amount_vnd');
            $table->timestamp('expires_at');
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('abandoned_at')->nullable();
            $table->timestamps();
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_payment_intents');
    }
};
