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
        Schema::table('hotel_info', function (Blueprint $table) {
            if (!Schema::hasColumn('hotel_info', 'bank_id')) {
                $table->string('bank_id')->nullable()->after('email');
            }
            if (!Schema::hasColumn('hotel_info', 'bank_account')) {
                $table->string('bank_account')->nullable()->after('bank_id');
            }
            if (!Schema::hasColumn('hotel_info', 'bank_account_name')) {
                $table->string('bank_account_name')->nullable()->after('bank_account');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_info', function (Blueprint $table) {
            //
        });
    }
};
