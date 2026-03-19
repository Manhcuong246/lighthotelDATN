<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tạo bảng cho Messenger integration
     */
    public function up(): void
    {
        Schema::create('messenger_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('facebook_user_id', 50)->unique();
            $table->string('facebook_sender_name', 255)->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedInteger('message_count')->default(0);
            $table->timestamps();
            $table->index('facebook_user_id');
        });

        Schema::create('messenger_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('messenger_conversations')->onDelete('cascade');
            $table->string('facebook_message_id', 100)->nullable();
            $table->text('content');
            $table->enum('role', ['user', 'assistant']);
            $table->boolean('is_from_facebook')->default(false);
            $table->enum('status', ['sent', 'delivered', 'read'])->default('sent');
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messenger_messages');
        Schema::dropIfExists('messenger_conversations');
    }
};
