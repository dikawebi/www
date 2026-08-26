# ============================================================
# export.ps1 - Export Docker setup asset-tagging ke folder bundle
# Pemakaian:
#   .\export.ps1                     -> export lengkap (termasuk image)
#   .\export.ps1 -SkipImage          -> tanpa image (PC baru akan build sendiri)
#   .\export.ps1 -OutputDir D:\backup
# ============================================================
param(
    [string]$OutputDir = "docker-export",
    [switch]$SkipImage
)

$ErrorActionPreference = "Stop"
$Root = $PSScriptRoot

Write-Host "=== Export Docker Setup: Asset Tagging ===" -ForegroundColor Cyan
Write-Host "Output: $OutputDir"

if (-not $SkipImage) {
    # Pastikan container berjalan untuk dump database
    $dbRunning = docker ps --filter "name=asset_tagging_db" --filter "status=running" -q
    if (-not $dbRunning) {
        Write-Host "ERROR: Container 'asset_tagging_db' tidak berjalan. Jalankan dulu:" -ForegroundColor Red
        Write-Host "  docker compose --env-file .env.docker up -d"
        exit 1
    }
}

# 1. Siapkan struktur folder
$dirs = @("$OutputDir", "$OutputDir\.docker\nginx")
foreach ($d in $dirs) {
    New-Item -ItemType Directory -Force -Path (Join-Path $Root $d) | Out-Null
}

# 2. Salin file konfigurasi minimal yang dibutuhkan PC baru
Copy-Item "$Root\docker-compose.yml"            "$OutputDir\"              -Force
Copy-Item "$Root\.env.docker"                   "$OutputDir\"              -Force
Copy-Item "$Root\.docker\nginx\default.conf"    "$OutputDir\.docker\nginx\" -Force
Copy-Item "$Root\import.ps1"                    "$OutputDir\"              -Force

if ($SkipImage) {
    # Jika tanpa image, sertakan Dockerfile + entrypoint agar bisa build
    New-Item -ItemType Directory -Force -Path "$OutputDir\.docker\php" | Out-Null
    Copy-Item "$Root\.docker\php\Dockerfile"     "$OutputDir\.docker\php\" -Force
    Copy-Item "$Root\.docker\php\entrypoint.sh"  "$OutputDir\.docker\php\" -Force
    Copy-Item "$Root\.docker\php\opcache.ini"    "$OutputDir\.docker\php\" -Force
    Copy-Item "$Root\.docker\php\uploads.ini"    "$OutputDir\.docker\php\" -Force
}

# 3. Dump database PostgreSQL
Write-Host "[1/4] Dump database PostgreSQL..." -ForegroundColor Yellow
cmd /c "docker exec asset_tagging_db pg_dump -U postgres asset_tagging > $OutputDir\db_backup.sql"
if ($LASTEXITCODE -ne 0) { Write-Host "ERROR: pg_dump gagal" -ForegroundColor Red; exit 1 }

# 4. Backup volume storage (file upload)
Write-Host "[2/4] Backup storage volume..." -ForegroundColor Yellow
docker run --rm -v asset-tagging_storage-data:/data -v "${Root}:/hostroot" alpine `
    sh -c "cd /data && tar czf /hostroot/$OutputDir/storage-backup.tar.gz ."
if ($LASTEXITCODE -ne 0) { Write-Host "ERROR: backup storage gagal" -ForegroundColor Red; exit 1 }

# 5. Export image aplikasi
if (-not $SkipImage) {
    Write-Host "[3/4] Export image (mungkin butuh beberapa menit)..." -ForegroundColor Yellow
    docker save asset-tagging-app:latest -o "$OutputDir\asset-tagging-app.tar"
    if ($LASTEXITCODE -ne 0) { Write-Host "ERROR: docker save gagal" -ForegroundColor Red; exit 1 }
} else {
    Write-Host "[3/4] Lewati export image (-SkipImage)" -ForegroundColor DarkGray
}

# 6. Ringkasan
Write-Host "[4/4] Selesai!" -ForegroundColor Green
Write-Host ""
Write-Host "Isi bundle:" -ForegroundColor Cyan
Get-ChildItem -Recurse -File $OutputDir | ForEach-Object {
    $sizeKB = [math]::Round($_.Length / 1KB)
    Write-Host ("  {0}  ({1} KB)" -f $_.FullName.Replace("$Root\", ""), $sizeKB)
}
Write-Host ""
Write-Host "Cara pakai di PC baru:" -ForegroundColor Cyan
Write-Host "  1. Salin seluruh folder '$OutputDir' ke PC baru"
if ($SkipImage) {
    Write-Host "  2. Salin juga source code project, lalu jalankan: docker compose --env-file .env.docker up -d --build"
} else {
    Write-Host "  2. Buka PowerShell di folder tersebut, jalankan: .\import.ps1"
}
