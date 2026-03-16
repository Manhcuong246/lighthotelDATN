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
            // Thêm số tiền đặt cọc (30% tổng tiền)
            $table->decimal('deposit_amount', 12, 2)->nullable()->after('total_price');
            
            // Thời gian yêu cầu thanh toán được gửi
            $table->timestamp('payment_request_sent_at')->nullable()->after('deposit_amount');
            
            // Thời gian thanh toán deposit thành công
            $table->timestamp('deposit_paid_at')->nullable()->after('payment_request_sent_at');
            
            // Payment method used
            $table->string('payment_method')->nullable()->after('deposit_paid_at');
            
            // Payment transaction ID
            $table->string('payment_transaction_id')->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'deposit_amount',
                'payment_request_sent_at',
                'deposit_paid_at',
                'payment_method',
                'payment_transaction_id'
            ]);
        });
    }
};
