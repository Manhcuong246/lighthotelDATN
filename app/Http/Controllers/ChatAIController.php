<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatAIController extends Controller
{
    /**
     * Xử lý tin nhắn từ Deep Chat - gọi Ollama hoặc Gemini
     */
    public function process(Request $request): JsonResponse
    {
        $messages = $request->input('messages', []);
        $userMessages = array_values(array_filter($messages, fn ($m) => ($m['role'] ?? '') === 'user'));
        $lastUser = end($userMessages);
        $userMessage = $lastUser['text'] ?? $messages[0]['text'] ?? null;

        if (! $userMessage || ! is_string($userMessage)) {
            return response()->json(['text' => 'Vui lòng nhập tin nhắn.']);
        }
        if (! is_string($userMessage) || strlen($userMessage) > 10000) {
            return response()->json(['text' => 'Tin nhắn không hợp lệ.']);
        }

        $aiContent = $this->generateResponse($userMessage, $messages);

        return response()->json(['text' => $aiContent]);
    }

    private function generateResponse(string $userMessage, array $messages): string
    {
        $geminiKey = config('gemini.api_key');
        $ollamaHost = config('ollama.host');

        if (! empty($geminiKey)) {
            $result = $this->callGemini($userMessage, $messages, $geminiKey);
            if ($result !== null) {
                return $result;
            }
        }

        if (! empty($ollamaHost)) {
            $result = $this->callOllama($userMessage, $ollamaHost, $messages);
            if ($result !== null) {
                return $result;
            }
        }

        return $this->fallbackResponse($userMessage);
    }

    private function callGemini(string $userMessage, array $messages, string $apiKey): ?string
    {
        $contents = $this->buildGeminiContents($messages);
        if (empty($contents)) {
            $contents = [['role' => 'user', 'parts' => [['text' => $userMessage]]]];
        }

        $payload = [
            'contents' => $contents,
            'systemInstruction' => [
                'parts' => [['text' => $this->buildSystemPrompt()]],
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1024,
            ],
        ];

        $primaryModel = config('gemini.model', 'gemini-2.5-flash');
        $models = array_unique(array_filter([
            $primaryModel,
            'gemini-2.5-flash',
            'gemini-2.5-flash-lite',
            'gemini-2.0-flash',
            'gemini-pro',
        ]));

        foreach ($models as $model) {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($model)
                . ':generateContent?key=' . urlencode($apiKey);

            try {
                $response = Http::timeout(30)->post($url, $payload);
                if ($response->successful()) {
                    $data = $response->json();
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    if (is_string($text) && trim($text) !== '') {
                        return trim($text);
                    }
                }
                Log::warning('Gemini API error', ['model' => $model, 'status' => $response->status(), 'body' => substr($response->body(), 0, 500)]);
                if ($response->status() !== 404) {
                    break;
                }
            } catch (\Exception $e) {
                Log::error('Gemini API exception', ['model' => $model, 'error' => $e->getMessage()]);
            }
        }

        return null;
    }

    private function buildGeminiContents(array $messages): array
    {
        $contents = [];
        foreach ($messages as $m) {
            $text = trim($m['text'] ?? '');
            if ($text === '') {
                continue;
            }
            $role = ($m['role'] ?? '') === 'ai' ? 'model' : 'user';
            $contents[] = ['role' => $role, 'parts' => [['text' => $text]]];
        }
        return $contents;
    }

    private function buildOllamaMessages(array $messages, string $lastUserMessage): array
    {
        $ollama = [['role' => 'system', 'content' => $this->buildSystemPrompt()]];
        foreach ($messages as $m) {
            $text = trim($m['text'] ?? '');
            if ($text === '') {
                continue;
            }
            $role = ($m['role'] ?? '') === 'ai' ? 'assistant' : 'user';
            $ollama[] = ['role' => $role, 'content' => $text];
        }
        if (count($ollama) === 1) {
            $ollama[] = ['role' => 'user', 'content' => $lastUserMessage];
        }
        return $ollama;
    }

    private function callOllama(string $userMessage, string $ollamaHost, array $messages = []): ?string
    {
        $model = config('ollama.model', 'llama3.2:1b');
        $ollamaMessages = $this->buildOllamaMessages($messages, $userMessage);

        try {
            $response = Http::timeout(60)->post(rtrim($ollamaHost, '/') . '/api/chat', [
                'model' => $model,
                'messages' => $ollamaMessages,
                'stream' => false,
            ]);

            if ($response->successful()) {
                $content = $response->json('message.content', '');
                if (is_string($content) && trim($content) !== '') {
                    return trim($content);
                }
            } else {
                Log::warning('Ollama API error', ['status' => $response->status(), 'host' => $ollamaHost]);
            }
        } catch (\Exception $e) {
            Log::error('Ollama API exception', ['host' => $ollamaHost, 'error' => $e->getMessage()]);
        }

        return null;
    }

    private function fallbackResponse(string $userMessage): string
    {
        $lower = mb_strtolower(trim($userMessage));

        if (str_contains($lower, 'xin chào') || str_contains($lower, 'hello') || str_contains($lower, 'chào') || $lower === 'yo' || $lower === 'hi') {
            return 'Xin chào! Tôi là trợ lý Light Hotel. Bạn có thể hỏi về giá phòng, đặt phòng hoặc dịch vụ.';
        }

        if (str_contains($lower, 'hôm nay') || str_contains($lower, 'ngày nay') || str_contains($lower, 'bây giờ') || str_contains($lower, 'ngày bao nhiêu') || str_contains($lower, 'mấy giờ')) {
            $date = Carbon::now('Asia/Ho_Chi_Minh')->locale('vi')->translatedFormat('l, d/m/Y');
            $time = Carbon::now('Asia/Ho_Chi_Minh')->format('H:i');
            return "Hôm nay là {$date}, giờ hiện tại {$time}.";
        }

        if ((str_contains($lower, 'giá') || str_contains($lower, 'tiền') || str_contains($lower, 'bao nhiêu')) && (str_contains($lower, 'phòng') || str_contains($lower, 'deluxe') || str_contains($lower, 'single') || str_contains($lower, 'suite'))) {
            return 'Light Hotel có nhiều loại phòng với giá khác nhau. Bạn quan tâm loại nào? (Single, Deluxe, Suite...)';
        }

        if (str_contains($lower, 'đặt phòng') || (str_contains($lower, 'đặt') && str_contains($lower, 'phòng'))) {
            return 'Để đặt phòng: chọn phòng trên trang chủ, xem chi tiết, nhấn "Đặt phòng". Thanh toán qua VNPay hoặc tại khách sạn.';
        }

        if (str_contains($lower, 'bạn là ai') || (str_contains($lower, 'tên') && str_contains($lower, 'bạn'))) {
            return 'Tôi là trợ lý ảo của Light Hotel. Bạn cần giúp gì?';
        }

        return 'Tôi đang gặp sự cố kết nối. Bạn có thể hỏi: "Hôm nay ngày mấy?", "Giá phòng?", "Cách đặt phòng?" hoặc gọi trực tiếp khách sạn.';
    }

    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
Bạn là trợ lý khách hàng của Light Hotel. Trả lời bằng tiếng Việt, ngắn gọn, thân thiện.
Hỗ trợ: giá phòng, đặt phòng, dịch vụ, địa chỉ, liên hệ.

QUY TẮC: Không trả lời bằng URL, đường dẫn file, mã lỗi (403, 404...). Chỉ dùng ngôn ngữ tự nhiên, hữu ích. Khi khách hỏi về ảnh phòng, hướng dẫn xem trên trang chi tiết phòng.
PROMPT;
    }
}
