# Shared helpers for ngrok-auto.ps1 / ngrok-local.ps1

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

function Backup-EnvFile {
    param([string]$EnvPath)
    $bak = "$EnvPath.bak.ngrok"
    Copy-Item -LiteralPath $EnvPath -Destination $bak -Force
    return $bak
}

function Write-EnvUtf8NoBom {
    param([string]$Path, [string[]]$Lines)
    $enc = New-Object System.Text.UTF8Encoding $false
    [System.IO.File]::WriteAllLines($Path, $Lines, $enc)
}

function Set-DotEnvNgrokMode {
    param(
        [string]$EnvPath,
        [string]$AppUrl
    )
    $url = $AppUrl.TrimEnd('/')
    $lines = [System.IO.File]::ReadAllLines($EnvPath)
    $out = [System.Collections.ArrayList]@()
    foreach ($line in $lines) {
        if ($line -match '^\s*APP_URL\s*=') {
            [void]$out.Add("APP_URL=$url")
        }
        elseif ($line -match '^\s*#?\s*TRUSTED_PROXIES\s*=') {
            [void]$out.Add('TRUSTED_PROXIES=*')
        }
        elseif ($line -match '^\s*SESSION_SECURE_COOKIE\s*=') {
            [void]$out.Add('SESSION_SECURE_COOKIE=true')
        }
        else {
            [void]$out.Add($line)
        }
    }
    Write-EnvUtf8NoBom -Path $EnvPath -Lines ($out.ToArray())
}

function Set-DotEnvLocalMode {
    param(
        [string]$EnvPath,
        [int]$Port
    )
    $localUrl = "http://127.0.0.1:$Port"
    $lines = [System.IO.File]::ReadAllLines($EnvPath)
    $out = [System.Collections.ArrayList]@()
    foreach ($line in $lines) {
        if ($line -match '^\s*APP_URL\s*=') {
            [void]$out.Add("APP_URL=$localUrl")
        }
        elseif ($line -match '^\s*#?\s*TRUSTED_PROXIES\s*=') {
            [void]$out.Add('# TRUSTED_PROXIES=')
        }
        elseif ($line -match '^\s*SESSION_SECURE_COOKIE\s*=') {
            [void]$out.Add('SESSION_SECURE_COOKIE=false')
        }
        else {
            [void]$out.Add($line)
        }
    }
    Write-EnvUtf8NoBom -Path $EnvPath -Lines ($out.ToArray())
}

function Get-NgrokHttpsUrlForPort {
    param(
        [int]$Port,
        [string]$ApiBase = 'http://127.0.0.1:4040'
    )
    try {
        $resp = Invoke-RestMethod -Uri "$ApiBase/api/tunnels" -TimeoutSec 3 -ErrorAction Stop
    }
    catch {
        return $null
    }
    if (-not $resp.tunnels) {
        return $null
    }
    $https = @($resp.tunnels | Where-Object { $_.public_url -like 'https://*' })
    if ($https.Count -eq 0) {
        return $null
    }
    $matchAddr = @($https | Where-Object {
            $a = [string]$_.config.addr
            $a -match ":${Port}$"
        })
    if ($matchAddr.Count -ge 1) {
        return ([string]$matchAddr[0].public_url).TrimEnd('/')
    }
    return ([string]$https[0].public_url).TrimEnd('/')
}
