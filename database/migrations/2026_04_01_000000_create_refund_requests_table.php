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
        Schema::create('refund_requests', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $blueprint->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Tài khoản ngân hàng
            $blueprint->string('account_name');
            $blueprint->string('account_number');
            $blueprint->string('bank_name');
            $blueprint->string('qr_image')->nullable();
            
            // Thông tin hoàn tiền
            $blueprint->integer('refund_percentage'); // 100, 50, 0
            $blueprint->decimal('refund_amount', 15, 2);
            $blueprint->text('note')->nullable();
            
            // Thông tin Admin xử lý
            $blueprint->text('admin_note')->nullable();
            $blueprint->string('refund_proof_image')->nullable();
            
            // Trạng thái
            $blueprint->enum('status', ['pending_refund', 'refunded', 'rejected'])->default('pending_refund');
            
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};
