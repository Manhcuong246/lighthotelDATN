param(
    # Náº¿u = 0: Ä‘á»c APP_PORT tá»« .env á»Ÿ root repo; khÃ´ng cÃ³ thÃ¬ 8088
    [int]$Port = 0
)

$ErrorActionPreference = 'Stop'

function Get-RepoRoot {
    return (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
}

function Read-AppPortFromEnv {
    param([string]$EnvPath)
    if (-not (Test-Path $EnvPath)) {
        return $null
    }
    $matchLine = Get-Content -LiteralPath $EnvPath -ErrorAction SilentlyContinue |
        Where-Object { $_ -match '^\s*APP_PORT\s*=' } |
        Select-Object -First 1
    if (-not $matchLine) {
        return $null
    }
    if ($matchLine -match '^\s*APP_PORT\s*=\s*(\d+)') {
        return [int]$Matches[1]
    }
    return $null
}

if (-not (Get-Command ngrok -ErrorAction SilentlyContinue)) {
    Write-Error "KhÃ´ng tÃ¬m tháº¥y ngrok trong PATH. CÃ i táº¡i https://ngrok.com/download vÃ  cháº¡y: ngrok config add-authtoken <token>"
}

$repo = Get-RepoRoot
$envFile = Join-Path $repo '.env'

if ($Port -le 0) {
    $parsed = Read-AppPortFromEnv $envFile
    $Port = if ($null -ne $parsed) { $parsed } else { 8088 }
}

Write-Host ""
Write-Host "Tunnel -> http://127.0.0.1:$Port (Docker: docker compose up -d | Artisan: php artisan serve --host=127.0.0.1 --port=$Port)" -ForegroundColor Cyan
Write-Host ""
Write-Host "Sau khi cÃ³ URL https://.... (Forwarding trong UI ngrok), cáº­p nháº­t .env:" -ForegroundColor Yellow
Write-Host "  APP_URL=<URL HTTPS, khÃ´ng cÃ³ / cuá»‘i>"
Write-Host "  TRUSTED_PROXIES=*"
Write-Host "  SESSION_SECURE_COOKIE=true"
Write-Host ""
Write-Host "Docker Laravel Ä‘á»c APP_URL lÃºc táº¡o container â€” cháº¡y:" -ForegroundColor Yellow
Write-Host "  docker compose up -d --force-recreate app"
Write-Host "  docker compose exec app php artisan config:clear"
Write-Host ""
Write-Host "VNPay sandbox: Return URL pháº£i khá»›p {APP_URL}/payment/vnpay/return (khai bÃ¡o trÃªn cá»•ng merchant)." -ForegroundColor Yellow
Write-Host ""
Write-Host "Má»™t lá»‡nh tá»± Ä‘á»™ng .env + Docker:  .\scripts\ngrok-auto.ps1  (ngrok má»Ÿ minimize riÃªng)." -ForegroundColor Green
Write-Host ""

& ngrok http $Port
