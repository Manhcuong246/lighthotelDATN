param(
  [int]$Parallel = 2
)

$ErrorActionPreference = "Stop"

$logDir = "storage/logs"
if (!(Test-Path $logDir)) {
  New-Item -ItemType Directory -Force -Path $logDir | Out-Null
}

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$logFile = Join-Path $logDir "full-test-$timestamp.log"
$reportFile = Join-Path $logDir "full-test-$timestamp-report.txt"

Write-Host "Running full test suite..."
Write-Host "Log: $logFile"

# Chạy test và ghi log song song ra file
# Lưu ý: `--parallel` yêu cầu ParaTest (brianium/paratest) >= 7.x.
$useParallel = $Parallel -gt 1 -and (Test-Path "vendor\bin\paratest")
if ($useParallel) {
  Write-Host "Parallel mode enabled (ParaTest detected): --parallel $Parallel"
  php artisan test --parallel $Parallel 2>&1 | Tee-Object -FilePath $logFile
} else {
  if ($Parallel -gt 1) {
    Write-Host "Parallel mode disabled (ParaTest not found). Running without --parallel."
  }
  php artisan test 2>&1 | Tee-Object -FilePath $logFile
}
$exitCode = $LASTEXITCODE

$content = Get-Content $logFile -Raw

function Get-LastLines([string]$text, [int]$count) {
  $lines = $text -split "`n"
  if ($lines.Count -le $count) { return ($lines -join "`n") }
  return (($lines | Select-Object -Last $count) -join "`n")
}

$summaryLine = ($content -split "`n" | Where-Object { $_ -match "Tests:\s+\d+.*passed" } | Select-Object -First 1)
if (-not $summaryLine) {
  $summaryLine = ($content -split "`n" | Where-Object { $_ -match "FAILED" -or $_ -match "ERROR" } | Select-Object -First 1)
}

$tail = Get-LastLines $content 80

"Full test report" | Out-File -FilePath $reportFile -Encoding utf8
"Timestamp: $timestamp" | Out-File -FilePath $reportFile -Append -Encoding utf8
"Exit code: $exitCode" | Out-File -FilePath $reportFile -Append -Encoding utf8
"Summary: $summaryLine" | Out-File -FilePath $reportFile -Append -Encoding utf8
"" | Out-File -FilePath $reportFile -Append -Encoding utf8
"Last log lines (for quick diagnosis):" | Out-File -FilePath $reportFile -Append -Encoding utf8
$tail | Out-File -FilePath $reportFile -Append -Encoding utf8

Write-Host ""
Write-Host "Report generated: $reportFile"

exit $exitCode

