<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('room_types', function (Blueprint $table) {
        $table->integer('capacity')->after('name'); // số người
        $table->decimal('price',10,2)->after('capacity'); // giá
    });
}

public function down()
{
    Schema::table('room_types', function (Blueprint $table) {
        $table->dropColumn(['capacity','price']);
    });
}
};
