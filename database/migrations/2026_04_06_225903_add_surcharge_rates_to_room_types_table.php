<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->decimal('adult_surcharge_rate', 5, 4)->nullable()->after('child_price')
                  ->comment('Phụ phí NL thêm = rate × base_price/đêm. NULL = dùng default config.');
            $table->decimal('child_surcharge_rate', 5, 4)->nullable()->after('adult_surcharge_rate')
                  ->comment('Phụ phí trẻ 6–11 thêm = rate × base_price/đêm. NULL = dùng default config.');
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn(['adult_surcharge_rate', 'child_surcharge_rate']);
        });
    }
};
