<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoice_items')) {
            return;
        }

        Schema::table('invoice_items', function (Blueprint $table) {
            if (! Schema::hasColumn('invoice_items', 'guest_adults')) {
                $table->unsignedSmallInteger('guest_adults')->nullable()->after('description');
            }
            if (! Schema::hasColumn('invoice_items', 'guest_children_6_11')) {
                $table->unsignedSmallInteger('guest_children_6_11')->nullable()->after('guest_adults');
            }
            if (! Schema::hasColumn('invoice_items', 'guest_children_0_5')) {
                $table->unsignedSmallInteger('guest_children_0_5')->nullable()->after('guest_children_6_11');
            }
        });

        if (Schema::hasColumn('invoice_items', 'description')) {
            DB::statement('ALTER TABLE `invoice_items` MODIFY `description` TEXT NOT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoice_items')) {
            return;
        }

        Schema::table('invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_items', 'guest_children_0_5')) {
                $table->dropColumn('guest_children_0_5');
            }
            if (Schema::hasColumn('invoice_items', 'guest_children_6_11')) {
                $table->dropColumn('guest_children_6_11');
            }
            if (Schema::hasColumn('invoice_items', 'guest_adults')) {
                $table->dropColumn('guest_adults');
            }
        });

        if (Schema::hasColumn('invoice_items', 'description')) {
            DB::statement('ALTER TABLE `invoice_items` MODIFY `description` VARCHAR(255) NOT NULL');
        }
    }
};
