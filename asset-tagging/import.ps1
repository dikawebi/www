# ============================================================
# import.ps1 - Import Docker setup asset-tagging di PC baru
# Jalankan dari dalam folder bundle hasil export.ps1
# Pemakaian:
#   .\import.ps1              -> load image + start + restore data
#   .\import.ps1 -NoData      -> tanpa restore data lama (database kosong)
# ============================================================
param(
    [switch]$NoData
)

$ErrorActionPreference = "Stop"
$Root = $PSScriptRoot

Write-Host "=== Import Docker Setup: Asset Tagging ===" -ForegroundColor Cyan

# 0. Validasi prasyarat
try { docker version *> $null } catch {
    Write-Host "ERROR: Docker tidak terpasang atau tidak berjalan." -ForegroundColor Red; exit 1
}

foreach ($f in @("docker-compose.yml", ".env.docker")) {
    if (-not (Test-Path (Join-Path $Root $f))) {
        Write-Host "ERROR: File '$f' tidak ditemukan. Jalankan script ini dari folder bundle." -ForegroundColor Red
        exit 1
    }
}

# 1. Load image jika ada
$imageFile = Join-Path $Root "asset-tagging-app.tar"
if (Test-Path $imageFile) {
    Write-Host "[1/4] Load image dari asset-tagging-app.tar..." -ForegroundColor Yellow
    docker load -i $imageFile
    if ($LASTEXITCODE -ne 0) { Write-Host "ERROR: docker load gagal" -ForegroundColor Red; exit 1 }
} else {
    Write-Host "[1/4] File image tidak ditemukan, akan build dari source (--build)..." -ForegroundColor Yellow
}

# 2. Start stack
Write-Host "[2/4] Menjalankan stack Docker..." -ForegroundColor Yellow
Push-Location $Root
if (Test-Path $imageFile) {
    docker compose --env-file .env.docker up -d
} else {
    # Butuh source code lengkap di folder ini jika build
    docker compose --env-file .env.docker up -d --build
}
if ($LASTEXITCODE -ne 0) { Pop-Location; Write-Host "ERROR: compose up gagal" -ForegroundColor Red; exit 1 }

# 3. Tunggu database siap
Write-Host "[3/4] Menunggu database siap..." -ForegroundColor Yellow
$timeout = 60; $elapsed = 0
while ($elapsed -lt $timeout) {
    $healthy = docker ps --filter "name=asset_tagging_db" --filter "status=running" --format "{{.Status}}"
    if ($healthy -match "healthy") { break }
    Start-Sleep -Seconds 2; $elapsed += 2
}
if ($elapsed -ge $timeout) { Pop-Location; Write-Host "ERROR: Database tidak kunjung sehat" -ForegroundColor Red; exit 1 }
Write-Host "      Database siap."

# 4. Restore data
if (-not $NoData -and (Test-Path (Join-Path $Root "db_backup.sql"))) {
    Write-Host "[4/4] Restore database & storage..." -ForegroundColor Yellow

    # Tunggu app selesai migrate (entrypoint), cek dengan mencoba koneksi psql
    cmd /c "docker exec -i asset_tagging_db psql -U postgres -d asset_tagging < db_backup.sql"
    if ($LASTEXITCODE -ne 0) { Write-Host "WARNING: restore database bermasalah, cek output di atas" -ForegroundColor Yellow }

    $storageFile = Join-Path $Root "storage-backup.tar.gz"
    if (Test-Path $storageFile) {
        docker run --rm -v asset-tagging_storage-data:/data -v "${Root}:/hostroot" alpine `
            sh -c "cd /data && tar xzf /hostroot/storage-backup.tar.gz"
        if ($LASTEXITCODE -ne 0) { Write-Host "WARNING: restore storage bermasalah" -ForegroundColor Yellow }
    }
} else {
    Write-Host "[4/4] Lewati restore data (-NoData atau backup tidak ada)" -ForegroundColor DarkGray
    Write-Host "      Jalankan seeder manual jika perlu:" -ForegroundColor DarkGray
    Write-Host "      docker compose --env-file .env.docker exec app php artisan db:seed --force" -ForegroundColor DarkGray
}
Pop-Location

Write-Host ""
Write-Host "=== Selesai! ===" -ForegroundColor Green
Write-Host "App            : http://localhost:8000" -ForegroundColor Cyan
Write-Host "Filament panel : http://localhost:8000/asset-tagging/login" -ForegroundColor Cyan
Write-Host ""
Write-Host "Catatan: restart nginx sekali jika halaman masih 502:"
Write-Host "  docker restart asset_tagging_nginx"
