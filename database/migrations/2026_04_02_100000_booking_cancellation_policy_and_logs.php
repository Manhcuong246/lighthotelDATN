<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM(
            'pending',
            'confirmed',
            'cancellation_pending',
            'cancelled',
            'refunded',
            'completed'
        ) NOT NULL DEFAULT 'pending'");

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('status');
            }
            if (! Schema::hasColumn('bookings', 'cancellation_requested_at')) {
                $table->timestamp('cancellation_requested_at')->nullable()->after('cancellation_reason');
            }
        });

        Schema::table('room_types', function (Blueprint $table) {
            if (! Schema::hasColumn('room_types', 'is_non_refundable')) {
                $table->boolean('is_non_refundable')->default(false)->after('status');
            }
        });

        Schema::table('hotel_info', function (Blueprint $table) {
            if (! Schema::hasColumn('hotel_info', 'default_check_in_time')) {
                $table->string('default_check_in_time', 8)->default('14:00:00')->after('email');
            }
            if (! Schema::hasColumn('hotel_info', 'cancel_free_hours')) {
                $table->unsignedSmallInteger('cancel_free_hours')->default(48)->after('default_check_in_time');
            }
            if (! Schema::hasColumn('hotel_info', 'cancel_mid_hours_low')) {
                $table->unsignedSmallInteger('cancel_mid_hours_low')->default(24)->after('cancel_free_hours');
            }
            if (! Schema::hasColumn('hotel_info', 'cancel_penalty_mid_percent')) {
                $table->unsignedTinyInteger('cancel_penalty_mid_percent')->default(50)->after('cancel_mid_hours_low');
            }
            if (! Schema::hasColumn('hotel_info', 'cancel_penalty_short_percent')) {
                $table->unsignedTinyInteger('cancel_penalty_short_percent')->default(100)->after('cancel_penalty_mid_percent');
            }
            if (! Schema::hasColumn('hotel_info', 'cancel_require_admin_when_penalty')) {
                $table->boolean('cancel_require_admin_when_penalty')->default(true)->after('cancel_penalty_short_percent');
            }
        });

        Schema::table('booking_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_logs', 'actor_user_id')) {
                $table->foreignId('actor_user_id')->nullable()->after('booking_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('booking_logs', 'note')) {
                $table->text('note')->nullable()->after('new_status');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'refund_penalty_amount')) {
                $table->decimal('refund_penalty_amount', 12, 2)->nullable()->after('refund_completed_at');
            }
            if (! Schema::hasColumn('payments', 'refund_eligible_amount')) {
                $table->decimal('refund_eligible_amount', 12, 2)->nullable()->after('refund_penalty_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'refund_eligible_amount')) {
                $table->dropColumn('refund_eligible_amount');
            }
            if (Schema::hasColumn('payments', 'refund_penalty_amount')) {
                $table->dropColumn('refund_penalty_amount');
            }
        });

        Schema::table('booking_logs', function (Blueprint $table) {
            if (Schema::hasColumn('booking_logs', 'note')) {
                $table->dropColumn('note');
            }
            if (Schema::hasColumn('booking_logs', 'actor_user_id')) {
                $table->dropForeign(['actor_user_id']);
                $table->dropColumn('actor_user_id');
            }
        });

        Schema::table('hotel_info', function (Blueprint $table) {
            foreach ([
                'cancel_require_admin_when_penalty',
                'cancel_penalty_short_percent',
                'cancel_penalty_mid_percent',
                'cancel_mid_hours_low',
                'cancel_free_hours',
                'default_check_in_time',
            ] as $col) {
                if (Schema::hasColumn('hotel_info', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('room_types', function (Blueprint $table) {
            if (Schema::hasColumn('room_types', 'is_non_refundable')) {
                $table->dropColumn('is_non_refundable');
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'cancellation_requested_at')) {
                $table->dropColumn('cancellation_requested_at');
            }
            if (Schema::hasColumn('bookings', 'cancellation_reason')) {
                $table->dropColumn('cancellation_reason');
            }
        });

        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM(
            'pending',
            'confirmed',
            'cancelled',
            'completed'
        ) NOT NULL DEFAULT 'pending'");
    }
};
