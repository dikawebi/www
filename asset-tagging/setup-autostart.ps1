# ============================================================
# setup-autostart.ps1 - Auto-start Docker stack + tunnel ngrok
# Jalankan SEKALI di PC kantor (sebagai user biasa):
#   .\setup-autostart.ps1 -Domain calm-avatar-exclude.ngrok-free.dev -ProjectDir C:\apps\docker-export
# Hapus semua auto-start:
#   .\setup-autostart.ps1 -Domain x -ProjectDir C:\apps\docker-export -Remove
# ============================================================
param(
    [Parameter(Mandatory = $true)]
    [string]$Domain,
    [string]$ProjectDir = "C:\apps\docker-export",
    [switch]$Remove
)

$ErrorActionPreference = "Stop"
$Root = $PSScriptRoot

if (-not $Remove -and -not (Test-Path (Join-Path $ProjectDir "docker-compose.yml"))) {
    Write-Host "ERROR: '$ProjectDir' tidak berisi docker-compose.yml." -ForegroundColor Red
    exit 1
}

$taskCompose = "AssetTagging-Stack"
$taskNgrok   = "AssetTagging-Ngrok"

if ($Remove) {
    foreach ($t in @($taskCompose, $taskNgrok)) {
        Unregister-ScheduledTask -TaskName $t -Confirm:$false -ErrorAction SilentlyContinue
        Write-Host "Task dihapus : $t" -ForegroundColor Yellow
    }
    Write-Host "Auto-start dimatikan." -ForegroundColor Green
    exit 0
}

# ---- Settings umum task ----
$settings = New-ScheduledTaskSettingsSet `
    -RestartCount 999 `
    -RestartInterval (New-TimeSpan -Minutes 1) `
    -StartWhenAvailable `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -ExecutionTimeLimit ([TimeSpan]::Zero)

# ---- Task 1: pastikan stack Docker jalan (30 detik setelah login) ----
$actionCompose = New-ScheduledTaskAction -Execute "powershell.exe" `
    -Argument "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -Command `"Set-Location '$ProjectDir'; docker compose --env-file .env.docker up -d`""
$triggerCompose = New-ScheduledTaskTrigger -AtLogOn
$triggerCompose.Delay = "PT30S"
Register-ScheduledTask -TaskName $taskCompose `
    -Action $actionCompose -Trigger $triggerCompose -Settings $settings `
    -Description "Asset Tagging: start docker compose stack" -Force | Out-Null
Write-Host "[OK] Task terpasang : $taskCompose" -ForegroundColor Green

# ---- Task 2: tunnel ngrok (90 detik setelah login, hidden, auto-restart) ----
$ngrokCmd = "ngrok http --url=$Domain 8000"
$actionNgrok = New-ScheduledTaskAction -Execute "powershell.exe" `
    -Argument "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -Command `"$ngrokCmd`""
$triggerNgrok = New-ScheduledTaskTrigger -AtLogOn
$triggerNgrok.Delay = "PT90S"
Register-ScheduledTask -TaskName $taskNgrok `
    -Action $actionNgrok -Trigger $triggerNgrok -Settings $settings `
    -Description "Asset Tagging: ngrok tunnel" -Force | Out-Null
Write-Host "[OK] Task terpasang : $taskNgrok" -ForegroundColor Green

# ---- PC tidak boleh sleep saat dicolok listrik ----
powercfg /change standby-timeout-ac 0
powercfg /change monitor-timeout-ac 20
Write-Host "[OK] Sleep dinonaktifkan (saat charger terpasang)" -ForegroundColor Green

Write-Host ""
Write-Host "=== Selesai! ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Sisa 2 hal yang harus diset MANUAL (sekali saja):" -ForegroundColor Yellow
Write-Host "1. Docker Desktop -> Settings -> General -> centang:"
Write-Host "   'Start Docker Desktop when you sign in'"
Write-Host "2. Setelah itu logout/login ulang untuk menguji semuanya."
Write-Host ""
Write-Host "Aplikasi akan otomatis online di:" -ForegroundColor Cyan
Write-Host "  https://$Domain/asset-tagging/login"
