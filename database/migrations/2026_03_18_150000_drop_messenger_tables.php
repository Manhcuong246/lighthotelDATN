<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Xóa bảng Messenger (đã bỏ tích hợp Facebook)
     */
    public function up(): void
    {
        Schema::dropIfExists('messenger_messages');
        Schema::dropIfExists('messenger_conversations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không khôi phục - Messenger đã bỏ hoàn toàn
    }
};
