<#
.SYNOPSIS
  Kiem tra tien trinh dang chiem CPU nhieu nhat tren Windows.

.DESCRIPTION
  - WMI Win32_PerfFormattedData_PerfProc_Process: gan real-time.
  - Bang CPU tich luy (giay) tu Get-Process.

.PARAMETER Top
  So dong (mac dinh 20).

.EXAMPLE
  .\scripts\check-cpu-hogs.ps1
  .\scripts\check-cpu-hogs.ps1 -Top 30
#>

param(
    [int]$Top = 20
)

$ErrorActionPreference = 'Stop'
$cores = [Environment]::ProcessorCount

Write-Host ''
Write-Host '=== CPU monitor (Windows) ===' -ForegroundColor Cyan
Write-Host ("Logical processors: {0} | Time: {1}" -f $cores, (Get-Date -Format 'yyyy-MM-dd HH:mm:ss')) -ForegroundColor Gray

# --- 1) Perf counter / formatted WMI ---
Write-Host ''
Write-Host ("--- Top {0} processes by CPU percent (PerfFormatted WMI) ---" -f $Top) -ForegroundColor Yellow

try {
    $perf = Get-CimInstance -ClassName Win32_PerfFormattedData_PerfProc_Process |
        Where-Object {
            $_.Name -notmatch '^(Idle|_Total|System)$' -and
            $_.IDProcess -gt 0 -and
            $null -ne $_.PercentProcessorTime
        } |
        Sort-Object PercentProcessorTime -Descending |
        Select-Object -First ([Math]::Max($Top, 5))

    $perf |
        Select-Object @{N='CPU_pct'; E={ [math]::Round([double]$_.PercentProcessorTime, 1) } }, @{N='PID'; E={ $_.IDProcess } }, @{N='Process'; E={ ($_.Name -replace '#\d+$', '') } } |
        Format-Table -AutoSize
}
catch {
    Write-Host ("WMI perf failed: {0}" -f $_.Exception.Message) -ForegroundColor Red
}

# --- 2) Cumulative CPU seconds ---
Write-Host ("--- Top {0} by cumulative CPU seconds (Get-Process) ---" -f $Top) -ForegroundColor Yellow

Get-Process |
    Sort-Object CPU -Descending |
    Select-Object -First $Top Name, Id,
        @{N='CPU_sec'; E={ [math]::Round([double]$_.CPU, 1) } },
        @{N='RAM_MB'; E={ [math]::Round($_.WorkingSet64 / 1MB, 0) } } |
    Format-Table -AutoSize

# --- 3) Quick totals ---
Write-Host '--- Totals (visible processes) ---' -ForegroundColor Yellow
$sessionCpu = (Get-Process | Measure-Object CPU -Sum).Sum
$sessionRam = (Get-Process | Measure-Object WorkingSet64 -Sum).Sum
Write-Host ('Sum CPU seconds (all listed processes): ~{0:N0} s' -f $sessionCpu)
Write-Host ('Sum WorkingSet RAM: ~{0:N0} MB' -f ($sessionRam / 1MB))
Write-Host ''
Write-Host 'Tip: Win+R -> resmon | Task Manager -> Details -> sort CPU' -ForegroundColor DarkGray
Write-Host ''
