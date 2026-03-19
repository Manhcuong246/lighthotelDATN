$ErrorActionPreference = "Stop"

function Require-Command($name) {
  if (-not (Get-Command $name -ErrorAction SilentlyContinue)) {
    throw "Missing command: $name. Install it and retry."
  }
}

Require-Command "php"
Require-Command "ngrok"

$port = 8000

Write-Host "Building assets (vite build)..."
if (Test-Path ".\package-lock.json") {
  Require-Command "npm"
  npm ci
  npm run build
} else {
  Write-Host "Skip npm build (no package-lock.json found)."
}

Write-Host "Starting Laravel on http://127.0.0.1:$port ..."
$phpProc = Start-Process -FilePath "php" -ArgumentList @("artisan","serve","--host=127.0.0.1","--port=$port") -PassThru

Start-Sleep -Seconds 2

Write-Host "Starting ngrok tunnel..."
$ngrokProc = Start-Process -FilePath "ngrok" -ArgumentList @("http","$port") -PassThru

Write-Host "Waiting ngrok API..."
for ($i=0; $i -lt 20; $i++) {
  try {
    $r = Invoke-RestMethod -Uri "http://127.0.0.1:4040/api/tunnels" -TimeoutSec 2
    if ($r.tunnels.Count -gt 0) { break }
  } catch {}
  Start-Sleep -Milliseconds 500
}

Write-Host "Syncing APP_URL from ngrok..."
php artisan app:sync-ngrok-url
php artisan config:clear | Out-Null

Write-Host ""
Write-Host "Done. Use the https ngrok URL printed above."
Write-Host "Press Ctrl+C in THIS window to stop."

try {
  Wait-Process -Id $phpProc.Id
} finally {
  if (!$phpProc.HasExited) { Stop-Process -Id $phpProc.Id -Force }
  if (!$ngrokProc.HasExited) { Stop-Process -Id $ngrokProc.Id -Force }
}

