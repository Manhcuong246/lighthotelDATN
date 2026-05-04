@php
    $brandName = $hotelInfo?->name ?? 'Light Hotel';
    $chatRequest = [
        'url' => url('/api/chat-process'),
        'method' => 'POST',
        'headers' => [
            'X-CSRF-TOKEN' => csrf_token(),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ],
        'additionalBodyProps' => ['user_id' => session()->getId()],
    ];
@endphp
<div id="chat-widget-container">
    <button type="button" class="chat-toggle btn btn-primary rounded-circle p-3" id="chat-toggle" title="Chat với trợ lý AI" aria-label="Mở chat">
        <i class="bi bi-chat-dots-fill fs-5"></i>
    </button>
    <div class="chat-panel shadow-lg" id="chat-panel" hidden>
        <div class="chat-panel-header d-flex align-items-center justify-content-between px-3 py-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-building text-white"></i>
                <span class="fw-semibold text-white">{{ $brandName }} - Chat AI</span>
            </div>
            <button type="button" class="btn btn-link text-white p-0 chat-close" id="chat-close" aria-label="Đóng">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="chat-panel-body" id="chat-panel-body">
            <div class="chat-placeholder text-center text-muted py-5" id="chat-placeholder">
                <i class="bi bi-chat-dots display-4 d-block mb-2"></i>
                <span>Đang tải chat...</span>
            </div>
        </div>
    </div>
</div>

<style>
#chat-widget-container { position: fixed; bottom: 24px; right: 24px; z-index: 1060; }
.chat-toggle {
    width: 56px;
    height: 56px;
    border: none;
    box-shadow: 0 8px 24px rgba(29, 78, 216, 0.45);
}
.chat-panel { position: absolute; bottom: 70px; right: 0; width: 400px; max-width: calc(100vw - 48px); height: 520px; background: #fff; border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; }
.chat-panel[hidden] { display: none !important; }
.chat-panel:not([hidden]) { display: flex !important; }
.chat-panel-header { background: linear-gradient(135deg, #0f172a, #1d4ed8); flex-shrink: 0; }
.chat-panel-body { flex: 1; min-height: 0; position: relative; height: 460px; }
.chat-panel-body deep-chat { display: block !important; width: 100% !important; height: 100% !important; min-height: 400px !important; }
@media (max-width: 480px) { .chat-panel { width: calc(100vw - 32px); height: 70vh; } .chat-panel-body { height: 60vh; } }
</style>

<script>
(function() {
    const toggle = document.getElementById('chat-toggle');
    const panel = document.getElementById('chat-panel');
    const closeBtn = document.getElementById('chat-close');
    const body = document.getElementById('chat-panel-body');
    const placeholder = document.getElementById('chat-placeholder');
    const chatRequest = @json($chatRequest);

    let chatInitialized = false;

    function initChat() {
        if (chatInitialized) return;
        chatInitialized = true;

        placeholder.remove();
        const chat = document.createElement('deep-chat');
        chat.id = 'deep-chat';
        chat.setAttribute('placeholder', 'Nhập tin nhắn...');
        chat.style.cssText = 'display: block; width: 100%; height: 100%; min-height: 400px;';
        body.appendChild(chat);

        customElements.whenDefined('deep-chat').then(function() {
            chat.connect = chatRequest;
            chat.requestBodyLimits = { maxMessages: 0 };
            chat.introMessage = { text: @json('Chào bạn! Tôi là trợ lý AI của '.$brandName.'. Bạn cần hỏi gì về giá phòng, đặt phòng hay dịch vụ?') };
        });
    }

    toggle.addEventListener('click', function() {
        panel.hidden = !panel.hidden;
        if (!panel.hidden) initChat();
    });
    closeBtn.addEventListener('click', function() {
        panel.hidden = true;
    });
})();
</script>
