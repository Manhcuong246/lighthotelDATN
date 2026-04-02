<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('refund_status', 32)->default('none')->after('paid_at');
            $table->string('refund_account_name')->nullable()->after('refund_status');
            $table->string('refund_account_number', 64)->nullable()->after('refund_account_name');
            $table->string('refund_qr_path')->nullable()->after('refund_account_number');
            $table->string('refund_proof_path')->nullable()->after('refund_qr_path');
            $table->text('refund_user_note')->nullable()->after('refund_proof_path');
            $table->text('refund_admin_note')->nullable()->after('refund_user_note');
            $table->timestamp('refund_requested_at')->nullable()->after('refund_admin_note');
            $table->timestamp('refund_completed_at')->nullable()->after('refund_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'refund_status',
                'refund_account_name',
                'refund_account_number',
                'refund_qr_path',
                'refund_proof_path',
                'refund_user_note',
                'refund_admin_note',
                'refund_requested_at',
                'refund_completed_at',
            ]);
        });
    }
};
