<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tạo bảng booking_rooms (trung gian)
        Schema::create('booking_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->decimal('price_per_night', 12, 2)->default(0); // giá 1 đêm của phòng này
            $table->integer('nights')->default(1);                  // số đêm
            $table->decimal('subtotal', 12, 2)->default(0);         // price_per_night * nights
            $table->timestamps();
        });

        // 2. Cho phép bookings.room_id nullable (để không phá dữ liệu booking cũ)
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('room_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_rooms');

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('room_id')->nullable(false)->change();
        });
    }
};
