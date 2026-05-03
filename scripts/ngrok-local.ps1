#requires -Version 5.1
<#
.SYNOPSIS
  ÄÆ°a .env vá» local http://127.0.0.1:{APP_PORT}, táº¯t TRUSTED_PROXIES / cookie secure, recreate app + config:clear
#>
param(
    [int]$Port = 0,
    [switch]$NoBackup,
    [switch]$NoDocker
)

$ErrorActionPreference = 'Stop'
$lib = Join-Path $PSScriptRoot 'ngrok-lib.ps1'
. $lib

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

Set-DotEnvLocalMode -EnvPath $envPath -Port $Port
Write-Host "ÄÃ£ Ä‘áº·t APP_URL=http://127.0.0.1:$Port (local)" -ForegroundColor Green

if ($NoDocker) {
    Write-Host "Bá» qua Docker (-NoDocker)." -ForegroundColor Yellow
    exit 0
}

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Error "KhÃ´ng cÃ³ docker trong PATH."
}

Push-Location $repo
try {
    & docker compose up -d --force-recreate app
    if ($LASTEXITCODE -ne 0) {
        throw "docker compose recreate app failed (exit $LASTEXITCODE)"
    }
    & docker compose exec -T app php artisan config:clear
    if ($LASTEXITCODE -ne 0) {
        throw "php artisan config:clear failed (exit $LASTEXITCODE)"
    }
}
finally {
    Pop-Location
}

Write-Host "ÄÃ£ vá» cháº¿ Ä‘á»™ local." -ForegroundColor Green
