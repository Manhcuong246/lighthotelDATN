#requires -Version 5.1
<#
.SYNOPSIS
  Má»™t láº§n cháº¡y: (tuá»³ chá»n) báº­t ngrok â†’ láº¥y URL HTTPS â†’ .env â†’ recreate container app â†’ config:clear

.EXAMPLE
  .\scripts\ngrok-auto.ps1

.EXAMPLE
  Ngrok Ä‘Ã£ má»Ÿ sáºµn (cÃ¹ng mÃ¡y):
  .\scripts\ngrok-auto.ps1 -UseExistingNgrok

.EXAMPLE
  Chá»‰ sá»­a .env + Docker, khÃ´ng spawn ngrok (Ä‘Ã£ cÃ³ tunnel khÃ¡c):
  .\scripts\ngrok-auto.ps1 -UseExistingNgrok
#>
param(
    [int]$Port = 0,
    [switch]$UseExistingNgrok,
    [switch]$NoDocker,
    [switch]$NoBackup
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$lib = Join-Path $PSScriptRoot 'ngrok-lib.ps1'
. $lib

if (-not (Get-Command ngrok -ErrorAction SilentlyContinue)) {
    Write-Error 'ChÆ°a cÃ³ ngrok trong PATH. CÃ i tá»« https://ngrok.com/download rá»“i cháº¡y: ngrok config add-authtoken YOUR_TOKEN'
}

$repo = Get-RepoRoot
$envPath = Join-Path $repo '.env'
if (-not (Test-Path $envPath)) {
    Write-Error "KhÃ´ng tháº¥y .env táº¡i $envPath"
}

if ($Port -le 0) {
    $parsed = Read-AppPortFromEnv $envPath
    $Port = if ($null -ne $parsed) { $parsed } else { 8088 }
}

if (-not $NoBackup) {
    $bak = Backup-EnvFile $envPath
    Write-Host "ÄÃ£ backup .env -> $bak" -ForegroundColor DarkGray
}

$ngrokStarted = $false
if (-not $UseExistingNgrok) {
    $existing = Get-NgrokHttpsUrlForPort -Port $Port
    if ($existing) {
        Write-Host "ÄÃ£ cÃ³ tunnel ngrok (API :4040): $existing" -ForegroundColor Cyan
        $publicUrl = $existing
    }
    else {
        Write-Host "Äang khá»Ÿi cháº¡y ngrok http $Port ..." -ForegroundColor Cyan
        Start-Process -FilePath 'ngrok' -ArgumentList @('http', $Port.ToString()) -WindowStyle Minimized
        $ngrokStarted = $true
        $deadline = (Get-Date).AddSeconds(50)
        $publicUrl = $null
        while ((Get-Date) -lt $deadline) {
            Start-Sleep -Seconds 1
            $publicUrl = Get-NgrokHttpsUrlForPort -Port $Port
            if ($publicUrl) {
                break
            }
        }
        if (-not $publicUrl) {
            Write-Error "KhÃ´ng láº¥y Ä‘Æ°á»£c URL HTTPS tá»« ngrok (http://127.0.0.1:4040/api/tunnels). Kiá»ƒm tra authtoken vÃ  cá»•ng $Port cÃ³ Ä‘ang nghe khÃ´ng."
        }
    }
}
else {
    $deadline = (Get-Date).AddSeconds(30)
    $publicUrl = $null
    while ((Get-Date) -lt $deadline) {
        $publicUrl = Get-NgrokHttpsUrlForPort -Port $Port
        if ($publicUrl) {
            break
        }
        Start-Sleep -Seconds 1
    }
    if (-not $publicUrl) {
        Write-Error "UseExistingNgrok: khÃ´ng tháº¥y tunnel HTTPS cho cá»•ng $Port trÃªn API :4040. HÃ£y cháº¡y ngrok trÆ°á»›c (vd. .\scripts\ngrok-http.ps1)."
    }
}

Write-Host "APP_URL (public): $publicUrl" -ForegroundColor Green
Set-DotEnvNgrokMode -EnvPath $envPath -AppUrl $publicUrl

if ($NoDocker) {
    Write-Host "ÄÃ£ bá» qua Docker (-NoDocker). GÃµ tay: php artisan config:clear (hoáº·c trong container)." -ForegroundColor Yellow
    Write-Host "VNPay Return URL: $publicUrl/payment/vnpay/return" -ForegroundColor Yellow
    exit 0
}

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Error "KhÃ´ng cÃ³ docker trong PATH; dÃ¹ng -NoDocker hoáº·c cÃ i Docker Desktop."
}

Push-Location $repo
try {
    Write-Host "docker compose up -d ..." -ForegroundColor Cyan
    & docker compose up -d
    if ($LASTEXITCODE -ne 0) {
        throw "docker compose up -d failed (exit $LASTEXITCODE)"
    }
    Write-Host "docker compose up -d --force-recreate app ..." -ForegroundColor Cyan
    & docker compose up -d --force-recreate app
    if ($LASTEXITCODE -ne 0) {
        throw "docker compose recreate app failed (exit $LASTEXITCODE)"
    }
    Write-Host "php artisan config:clear (container app) ..." -ForegroundColor Cyan
    & docker compose exec -T app php artisan config:clear
    if ($LASTEXITCODE -ne 0) {
        throw "php artisan config:clear failed (exit $LASTEXITCODE)"
    }
}
finally {
    Pop-Location
}

Write-Host ""
Write-Host "Xong. Má»Ÿ: $publicUrl" -ForegroundColor Green
Write-Host "VNPay sandbox Return URL cáº§n khá»›p: $publicUrl/payment/vnpay/return" -ForegroundColor Yellow
if ($ngrokStarted) {
    Write-Host "Ngrok Ä‘ang cháº¡y (cá»­a sá»• Ä‘Ã£ minimize). Web inspect: http://127.0.0.1:4040" -ForegroundColor DarkGray
}
