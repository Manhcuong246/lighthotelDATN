<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            // Sửa name thành nullable (chỉ người đại diện bắt buộc)
            $table->string('name')->nullable()->change();
            
            // Thêm flag đại diện
            $table->boolean('is_representative')->default(false)->after('name');
            
            // Index để tìm người đại diện nhanh
            $table->index(['booking_id', 'is_representative']);
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->dropColumn('is_representative');
            $table->dropIndex(['booking_id', 'is_representative']);
        });
    }
};
