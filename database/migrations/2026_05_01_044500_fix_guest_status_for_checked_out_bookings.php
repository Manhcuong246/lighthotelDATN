<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, add 'checked_out' to ENUM columns if not exists
        DB::statement("ALTER TABLE guests MODIFY COLUMN checkin_status ENUM('pending', 'checked_in', 'checked_out') DEFAULT 'pending'");
        DB::statement("ALTER TABLE booking_guests MODIFY COLUMN status ENUM('pending', 'checked_in', 'checked_out') DEFAULT 'pending'");

        // Also add checkin_status column to booking_guests if not exists
        if (!Schema::hasColumn('booking_guests', 'checkin_status')) {
            Schema::table('booking_guests', function (Blueprint $table) {
                $table->enum('checkin_status', ['pending', 'checked_in', 'checked_out'])->default('pending')->after('status');
            });
        } else {
            DB::statement("ALTER TABLE booking_guests MODIFY COLUMN checkin_status ENUM('pending', 'checked_in', 'checked_out') DEFAULT 'pending'");
        }

        // Fix guests table - update checkin_status to checked_out for completed bookings
        DB::table('guests')
            ->whereIn('booking_id', function($query) {
                $query->select('id')
                    ->from('bookings')
                    ->where('status', 'completed');
            })
            ->where('checkin_status', 'checked_in')
            ->update(['checkin_status' => 'checked_out']);

        // Fix booking_guests table - update status and checkin_status to checked_out
        DB::table('booking_guests')
            ->whereIn('booking_id', function($query) {
                $query->select('id')
                    ->from('bookings')
                    ->where('status', 'completed');
            })
            ->where(function($query) {
                $query->where('status', 'checked_in')
                    ->orWhere('checkin_status', 'checked_in');
            })
            ->update([
                'status' => 'checked_out',
                'checkin_status' => 'checked_out'
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không cần rollback
    }
};
