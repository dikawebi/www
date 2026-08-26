# ============================================================
# ngrok.ps1 - Jalankan stack Docker + tunnel ngrok
# Pemakaian:
#   .\ngrok.ps1 -Domain asset-tagging.ngrok-free.app
#   .\ngrok.ps1 -Domain asset-tagging.ngrok-free.app -SkipDocker
# Prasyarat:
#   1. ngrok terinstall: winget install ngrok.ngrok
#   2. Authtoken diset:  ngrok config add-authtoken <token>
#   3. Static domain sudah diklaim di dashboard.ngrok.com
# ============================================================
param(
    [Parameter(Mandatory = $true)]
    [string]$Domain,
    [switch]$SkipDocker
)

$ErrorActionPreference = "Stop"
$Root = $PSScriptRoot

if (-not $Domain -like "*.ngrok*" -and -not $Domain.Contains(".")) {
    Write-Host "ERROR: Domain tidak valid, contoh: asset-tagging.ngrok-free.app" -ForegroundColor Red
    exit 1
}

try { ngrok version *> $null } catch {
    Write-Host "ERROR: ngrok belum terinstall." -ForegroundColor Red
    Write-Host "Install dengan: winget install ngrok.ngrok" -ForegroundColor Yellow
    exit 1
}

# 1. Pastikan APP_URL di .env.docker sesuai domain ngrok
$envFile = Join-Path $Root ".env.docker"
$envContent = Get-Content $envFile -Raw
$newUrl = "https://$Domain"

if ($envContent -match "(?m)^APP_URL=(.*)$") {
    if ($Matches[1] -ne $newUrl) {
        $envContent = $envContent -replace "(?m)^APP_URL=.*$", "APP_URL=$newUrl"
        Set-Content $envFile $envContent -NoNewline
        Write-Host "[1/3] APP_URL diperbarui -> $newUrl" -ForegroundColor Yellow
    } else {
        Write-Host "[1/3] APP_URL sudah benar" -ForegroundColor DarkGray
    }
}

# 2. Start / refresh stack (recreate agar env baru terbaca)
if (-not $SkipDocker) {
    Write-Host "[2/3] Menjalankan stack Docker..." -ForegroundColor Yellow
    Push-Location $Root
    docker compose --env-file .env.docker up -d --force-recreate app queue nginx
    Pop-Location
    if ($LASTEXITCODE -ne 0) { Write-Host "ERROR: compose up gagal" -ForegroundColor Red; exit 1 }

    # Tunggu nginx siap
    $ok = $false
    foreach ($i in 1..15) {
        try {
            $r = Invoke-WebRequest -Uri "http://localhost:8000" -UseBasicParsing -TimeoutSec 5
            if ($r.StatusCode) { $ok = $true; break }
        } catch { Start-Sleep -Seconds 2 }
    }
    if (-not $ok) {
        Write-Host "WARNING: App belum merespons di localhost:8000, ngrok tetap dijalankan." -ForegroundColor Yellow
    }
} else {
    Write-Host "[2/3] Lewati start Docker (-SkipDocker)" -ForegroundColor DarkGray
}

# 3. Jalankan ngrok (blocking — Ctrl+C untuk stop)
Write-Host "[3/3] Memulai tunnel ngrok..." -ForegroundColor Yellow
Write-Host ""
Write-Host "  URL publik : https://$Domain" -ForegroundColor Green
Write-Host "  Filament   : https://$Domain/asset-tagging/login" -ForegroundColor Green
Write-Host ""
Write-Host "  Tekan Ctrl+C untuk menghentikan tunnel." -ForegroundColor DarkGray
Write-Host ""

ngrok http --url=$Domain --request-header-add "ngrok-skip-browser-warning: true" 8000
