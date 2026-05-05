<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_process_requires_user_message(): void
    {
        $response = $this->postJson('/api/chat-process', ['messages' => []]);

        $response->assertOk();
        $response->assertJsonFragment(['text' => 'Vui lòng nhập tin nhắn.']);
    }

    public function test_chat_process_rejects_overlong_message(): void
    {
        $long = str_repeat('a', 10001);
        $response = $this->postJson('/api/chat-process', [
            'messages' => [['role' => 'user', 'text' => $long]],
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['text' => 'Tin nhắn không hợp lệ.']);
    }
}
