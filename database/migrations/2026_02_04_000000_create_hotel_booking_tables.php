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
        // Create roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->timestamps();
        });

        // Create user_roles table
        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->primary(['user_id', 'role_id']);
        });

        // Create hotel_info table
        Schema::create('hotel_info', function (Blueprint $table) {
            $table->tinyInteger('id')->default(1)->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->timestamps();
        });

        // Create rooms table
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('type', 100)->nullable();
            $table->decimal('base_price', 10, 2)->nullable();
            $table->integer('max_guests')->nullable();
            $table->integer('beds')->nullable();
            $table->integer('baths')->nullable();
            $table->float('area')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['available', 'booked', 'maintenance'])->default('available');
            $table->timestamps();
        });

        // Create room_prices table
        Schema::create('room_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
        });

        // Create amenities table
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('icon_url', 255)->nullable();
            $table->timestamps();
        });

        // Create room_amenities table
        Schema::create('room_amenities', function (Blueprint $table) {
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->foreignId('amenity_id')->constrained()->onDelete('cascade');
            $table->primary(['room_id', 'amenity_id']);
        });

        // Create services table
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->decimal('price', 10, 2);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Create images table
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('image_url');
            $table->enum('image_type', ['hotel', 'room']);
            $table->timestamps();
        });

        // Create bookings table
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->date('check_in');
            $table->date('check_out');
            $table->dateTime('actual_check_in')->nullable();
            $table->dateTime('actual_check_out')->nullable();
            $table->integer('guests')->nullable();
            $table->decimal('total_price', 12, 2);
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->timestamps();
        });

        // Create room_booked_dates table
        Schema::create('room_booked_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->date('booked_date');
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->unique(['room_id', 'booked_date']);
            $table->timestamps();
        });

        // Create payments table
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->string('method', 50);
            $table->enum('status', ['pending', 'paid', 'failed']);
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });

        // Create booking_logs table
        Schema::create('booking_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->string('old_status', 50);
            $table->string('new_status', 50);
            $table->timestamp('changed_at')->useCurrent();
            $table->timestamps();
        });

        // Create reviews table
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->integer('rating')->checkBetween(1, 5);
            $table->text('comment')->nullable();
            $table->text('reply')->nullable();
            $table->dateTime('replied_at')->nullable();
            $table->timestamps();
        });

        // Create coupons table
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->integer('discount_percent');
            $table->date('expired_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Create site_contents table
        Schema::create('site_contents', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['banner', 'policy', 'about', 'footer']);
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_contents');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('booking_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('room_booked_dates');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('images');
        Schema::dropIfExists('services');
        Schema::dropIfExists('room_amenities');
        Schema::dropIfExists('amenities');
        Schema::dropIfExists('room_prices');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('hotel_info');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
    }
};
