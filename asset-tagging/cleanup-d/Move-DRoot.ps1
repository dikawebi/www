# Move-DRoot.ps1 - Pindahkan file root D sesuai laporan CSV yang disetujui user.
# Hanya mengeksekusi baris dengan setuju_YN = Y / YA / YES / 1 / OK / SETUJU.
# Aman: skip dest kosong (.ost/pagefile), guard system file, resolve konflik nama,
# tulis undo-log JSON + script Restore otomatis.
param(
  [string]$ReportCsv = "",
  [string]$ReportDir = "D:\_Grouped\_report",
  [string]$UndoDir = "D:\_Grouped\_report\undo",
  [switch]$DryRun,
  [switch]$AutoConfirm
)

$ErrorActionPreference = "Continue"

function Get-Approved([string]$v) {
  if ([string]::IsNullOrWhiteSpace($v)) { return $false }
  $t = $v.Trim().ToUpper()
  return ($t -eq "Y" -or $t -eq "YA" -or $t -eq "YES" -or $t -eq "1" -or $t -eq "OK" -or $t -eq "SETUJU")
}

function Resolve-Conflict([string]$dest) {
  if (-not (Test-Path -LiteralPath $dest)) { return $dest }
  $dir = [System.IO.Path]::GetDirectoryName($dest)
  $base = [System.IO.Path]::GetFileNameWithoutExtension($dest)
  $ext = [System.IO.Path]::GetExtension($dest)
  $n = 2
  while ($true) {
    $cand = Join-Path $dir ($base + " (" + $n + ")" + $ext)
    if (-not (Test-Path -LiteralPath $cand)) { return $cand }
    $n++
    if ($n -gt 999) { throw ("Terlalu banyak konflik untuk: " + $dest) }
  }
}

# --- Cari laporan terbaru jika tidak disebut ---
if ([string]::IsNullOrWhiteSpace($ReportCsv)) {
  $latest = Get-ChildItem -LiteralPath $ReportDir -Filter "report-*.csv" -File -ErrorAction SilentlyContinue |
    Sort-Object Name -Descending | Select-Object -First 1
  if (-not $latest) { Write-Output "ERROR: tidak ada report-*.csv di $ReportDir"; exit 1 }
  $ReportCsv = $latest.FullName
}
if (-not (Test-Path -LiteralPath $ReportCsv)) { Write-Output ("ERROR: file tidak ada: " + $ReportCsv); exit 1 }

$rows = Import-Csv -LiteralPath $ReportCsv
$approved = @($rows | Where-Object { Get-Approved $_.setuju_YN })
Write-Output ("REPORT=" + $ReportCsv)
Write-Output ("TOTAL_ROWS=" + $rows.Count)
Write-Output ("APPROVED=" + $approved.Count)

if ($approved.Count -eq 0) {
  Write-Output "Tidak ada baris disetujui (kolom setuju_YN kosong). Isi Y pada baris yang boleh dipindah, lalu jalankan lagi."
  exit 0
}

# --- Ringkasan per kategori untuk konfirmasi ---
$approved | Group-Object category | Sort-Object Name | ForEach-Object {
  Write-Output ("  PLAN " + $_.Name + " count=" + $_.Count)
}

if (-not $AutoConfirm -and -not $DryRun) {
  $ans = Read-Host "Lanjut pindahkan file di atas? (ketik YA untuk lanjut)"
  if ($ans.Trim().ToUpper() -ne "YA") { Write-Output "Dibatalkan user."; exit 0 }
}

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
New-Item -ItemType Directory -Force -Path $UndoDir | Out-Null
$moves = New-Object System.Collections.Generic.List[object]
$skipped = 0
$moved = 0

foreach ($r in $approved) {
  $src = [string]$r.source
  $dst = [string]$r.proposed_dest
  $short = [System.IO.Path]::GetFileName($src)

  # Guard: dest kosong (ost/pagefile/skip) -> lewati walau disetujui
  if ([string]::IsNullOrWhiteSpace($dst)) {
    Write-Output ("SKIP (tanpa destinasi): " + $src)
    $skipped++
    continue
  }
  # Guard: system file tidak boleh pindah
  if ($short -ieq "pagefile.sys") {
    Write-Output ("SKIP (system file): " + $src)
    $skipped++
    continue
  }
  if (-not (Test-Path -LiteralPath $src)) {
    Write-Output ("SKIP (source hilang): " + $src)
    $skipped++
    continue
  }
  if ($r.needs_review -eq "True" -and -not $AutoConfirm -and -not $DryRun) {
    $a2 = Read-Host ("Baris needs_review: " + $src + " -- tetap pindah? (YA/tidak)")
    if ($a2.Trim().ToUpper() -ne "YA") {
      Write-Output ("SKIP (needs_review ditolak): " + $src)
      $skipped++
      continue
    }
  }

  $destDir = [System.IO.Path]::GetDirectoryName($dst)
  if ($DryRun) {
    Write-Output ("WOULD-MOVE: " + $src + " -> " + $dst)
    $moved++
    continue
  }
  New-Item -ItemType Directory -Force -Path $destDir | Out-Null
  $finalDst = Resolve-Conflict $dst
  try {
    $size = (Get-Item -LiteralPath $src).Length
  } catch { $size = 0 }
  try {
    Move-Item -LiteralPath $src -Destination $finalDst -Force
    Write-Output ("MOVED: " + $src + " -> " + $finalDst)
    $moves.Add([pscustomobject]@{
      source = $src
      dest = $finalDst
      size_bytes = $size
      moved_at = (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
      category = [string]$r.category
    })
    $moved++
  } catch {
    Write-Output ("GAGAL: " + $src + " | " + $_.Exception.Message)
    $skipped++
  }
}

# --- Undo log + script restore ---
if ($moves.Count -gt 0) {
  $undoJson = Join-Path $UndoDir ("undo-" + $stamp + ".json")
  $moves | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $undoJson -Encoding UTF8
  $restorePs = Join-Path $UndoDir ("Restore-" + $stamp + ".ps1")
  $rlines = New-Object System.Collections.Generic.List[string]
  $rlines.Add("# Restore otomatis hasil Move-DRoot " + $stamp)
  $rlines.Add("# Menjalankan file ini mengembalikan file ke lokasi asal.")
  foreach ($m in $moves) {
    $d = [string]$m.dest
    $s = [string]$m.source
    # pakai literal path dengan single-quote yang di-escape
    $dq = $d.Replace("'", "''")
    $sq = $s.Replace("'", "''")
    $rlines.Add("if (Test-Path -LiteralPath '" + $dq + "') { Move-Item -LiteralPath '" + $dq + "' -Destination '" + $sq + "' -Force; Write-Output 'RESTORED: " + $sq + "' } else { Write-Output 'HILANG: " + $dq + "' }")
  }
  [System.IO.File]::WriteAllLines($restorePs, $rlines, [System.Text.Encoding]::UTF8)
  Write-Output ("UNDO_JSON=" + $undoJson)
  Write-Output ("RESTORE_SCRIPT=" + $restorePs)
}

Write-Output ("MOVED_COUNT=" + $moved)
Write-Output ("SKIPPED_COUNT=" + $skipped)
if ($DryRun) { Write-Output "Mode DryRun: tidak ada file dipindah." }
