<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function addSoftDeletes(string $table): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'deleted_at')) {
            return;
        }
        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->softDeletes();
        });
    }

    public function up(): void
    {
        foreach ([
            'users',
            'rooms',
            'room_types',
            'services',
            'bookings',
            'reviews',
            'payments',
            'invoices',
            'invoice_items',
            'coupons',
            'images',
        ] as $t) {
            $this->addSoftDeletes($t);
        }

        if (Schema::hasTable('room_booked_dates') && ! Schema::hasColumn('room_booked_dates', 'deleted_at')) {
            try {
                Schema::table('room_booked_dates', function (Blueprint $table) {
                    $table->dropUnique(['room_id', 'booked_date']);
                });
            } catch (\Throwable) {
                // Index name / driver khác — bỏ qua nếu đã không còn unique
            }

            Schema::table('room_booked_dates', function (Blueprint $table) {
                $table->softDeletes();
                $table->index(['room_id', 'booked_date']);
            });
        }

        if (Schema::hasTable('reviews')) {
            try {
                Schema::table('reviews', function (Blueprint $table) {
                    $table->dropUnique('reviews_user_id_room_id_unique');
                });
            } catch (\Throwable) {
                try {
                    Schema::table('reviews', function (Blueprint $table) {
                        $table->dropUnique(['user_id', 'room_id']);
                    });
                } catch (\Throwable) {
                    //
                }
            }
            try {
                Schema::table('reviews', function (Blueprint $table) {
                    $table->index(['user_id', 'room_id']);
                });
            } catch (\Throwable) {
                //
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reviews')) {
            try {
                Schema::table('reviews', function (Blueprint $table) {
                    $table->dropIndex(['user_id', 'room_id']);
                });
            } catch (\Throwable) {
                //
            }
            try {
                Schema::table('reviews', function (Blueprint $table) {
                    $table->unique(['user_id', 'room_id'], 'reviews_user_id_room_id_unique');
                });
            } catch (\Throwable) {
                //
            }
        }

        if (Schema::hasTable('room_booked_dates') && Schema::hasColumn('room_booked_dates', 'deleted_at')) {
            Schema::table('room_booked_dates', function (Blueprint $table) {
                try {
                    $table->dropIndex(['room_id', 'booked_date']);
                } catch (\Throwable) {
                    //
                }
                $table->dropSoftDeletes();
                $table->unique(['room_id', 'booked_date']);
            });
        }

        foreach (array_reverse([
            'images',
            'coupons',
            'invoice_items',
            'invoices',
            'payments',
            'reviews',
            'bookings',
            'services',
            'room_types',
            'rooms',
            'users',
        ]) as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'deleted_at')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
