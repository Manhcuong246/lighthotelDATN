param(
    # Docker Compose nginx (APP_PORT trong .env, vd. 8088); Artisan serve: -Port 8000
    [int]$Port = 8088
)

$ErrorActionPreference = 'Stop'
if (-not (Get-Command ngrok -ErrorAction SilentlyContinue)) {
    Write-Error "Không tìm thấy ngrok trong PATH. Cài tại https://ngrok.com/download và chạy: ngrok config add-authtoken <token>"
}

Write-Host ""
Write-Host "Tunnel -> http://127.0.0.1:$Port" -ForegroundColor Cyan
Write-Host "Terminal khác chạy Laravel:" -ForegroundColor Yellow
Write-Host "  php artisan serve --host=127.0.0.1 --port=$Port"
Write-Host ""
Write-Host "Sau khi có URL https://....ngrok-free.app (hoặc tên miền static):" -ForegroundColor Yellow
Write-Host "  .env  APP_URL=<URL không có / cuối>"
Write-Host "        TRUSTED_PROXIES=*"
Write-Host "        SESSION_SECURE_COOKIE=true"
Write-Host "  php artisan config:clear"
Write-Host ""

& ngrok http $Port
