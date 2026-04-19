<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Một số DB chỉ chạy migration gốc mà thiếu bảng booking_services — gây lỗi khi lưu dịch vụ kèm.
     */
    public function up(): void
    {
        if (Schema::hasTable('booking_services')) {
            return;
        }

        Schema::create('booking_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Không drop: bảng có thể đã tồn tại từ migration khác.
    }
};
