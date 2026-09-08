# Scan-DRoot.ps1 - Dry-run scan file level-1 di root D (read-only, tidak pindah/hapus)
# Output: JSON + CSV + HTML di D:\_Grouped\_report\
param(
  [string]$Root = "D:\",
  [string]$GroupedBase = "D:\_Grouped",
  [string]$OutputDir = "D:\_Grouped\_report"
)

$ErrorActionPreference = "Continue"
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
New-Item -ItemType Directory -Force -Path $OutputDir | Out-Null

# --- Daftar installed apps (untuk saran Sudah-Install) ---
$installedNames = @()
try {
  $regPaths = @(
    "HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\*",
    "HKLM:\SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall\*"
  )
  foreach ($rp in $regPaths) {
    $apps = Get-ItemProperty -Path $rp -ErrorAction SilentlyContinue | Where-Object { $_.DisplayName }
    foreach ($a in $apps) { $installedNames += $_.DisplayName.ToString().ToLower() }
  }
} catch { }

function Test-ProbablyInstalled([string]$baseName) {
  if ($installedNames.Count -eq 0) { return $false }
  $tokens = ($baseName.ToLower() -split '[-_ .\d]+' | Where-Object { $_.Length -ge 4 })
  foreach ($t in $tokens) {
    foreach ($inst in $installedNames) {
      if ($inst -like ("*" + $t + "*")) { return $true }
    }
  }
  return $false
}

function Get-Category([string]$name, [string]$ext) {
  $ln = $name.ToLower()
  $e = $ext.ToLower()
  if ($e -eq ".drawio") { return @("05-Diagram", "") }
  if (($ln.Contains("drawio")) -and ($e -eq ".pdf" -or $e -eq ".jpg" -or $e -eq ".jpeg" -or $e -eq ".png")) { return @("05-Diagram", "") }
  if ($e -eq ".pdf" -or $e -eq ".docx" -or $e -eq ".doc" -or $e -eq ".xlsx" -or $e -eq ".xlsm" -or $e -eq ".xls" -or $e -eq ".csv" -or $e -eq ".pptx" -or $e -eq ".ppt" -or $e -eq ".txt" -or $e -eq ".html" -or $e -eq ".htm") { return @("01-Dokumen", "") }
  if ($e -eq ".png" -or $e -eq ".jpg" -or $e -eq ".jpeg" -or $e -eq ".svg" -or $e -eq ".bmp" -or $e -eq ".gif" -or $e -eq ".webp") { return @("02-Gambar", "") }
  if ($e -eq ".exe" -or $e -eq ".msi" -or $e -eq ".bat" -or $e -eq ".cmd") { return @("03-Installer", "Belum-Install") }
  if ($e -eq ".zip" -or $e -eq ".rar" -or $e -eq ".7z" -or $e -eq ".iso" -or $e -eq ".tdbx") { return @("04-Arsip", "") }
  if ($e -eq ".py" -or $e -eq ".json" -or $e -eq ".xml" -or $e -eq ".pem" -or $e -eq ".log" -or $e -eq ".etl") { return @("06-Kode-Data", "") }
  return @("07-Lainnya", "")
}

$files = Get-ChildItem -LiteralPath $Root -File -Force -ErrorAction SilentlyContinue
$rows = New-Object System.Collections.Generic.List[object]
$destSeen = @{}

foreach ($f in $files) {
  $name = $f.Name
  $ext = $f.Extension
  $size = 0
  try { $size = $f.Length } catch { }
  $mod = ""
  try { $mod = $f.LastWriteTime.ToString("yyyy-MM-dd HH:mm") } catch { }

  $needsReview = $false
  $reason = ""
  $proposed = ""
  $cat = ""
  $sub = ""
  $suggestSudah = $false
  $conflict = $false

  if ($name -ieq "pagefile.sys") {
    $needsReview = $true
    $reason = "System file - skip permanen"
  }
  elseif ($ext -ieq ".ost") {
    $needsReview = $true
    $reason = "File Outlook .ost besar - skip dulu sesuai request"
  }
  elseif ($name -match '^\{[0-9A-Fa-f-]{36}\}\.zip$') {
    $needsReview = $true
    $reason = "Nama GUID aneh - cek manual sebelum dipindah"
    $r = Get-Category $name $ext
    $cat = $r[0]; $sub = $r[1]
    $destDir = Join-Path $GroupedBase $cat
    if ($sub -ne "") { $destDir = Join-Path $destDir $sub }
    $proposed = Join-Path $destDir $name
  }
  else {
    $r = Get-Category $name $ext
    $cat = $r[0]; $sub = $r[1]
    if ($cat -eq "03-Installer") {
      $suggestSudah = Test-ProbablyInstalled ([System.IO.Path]::GetFileNameWithoutExtension($name))
      if ($suggestSudah) { $reason = "Installer - saran Sudah-Install (cocok registry). Default tetap Belum-Install." }
      else { $reason = "Installer - default Belum-Install" }
    }
    elseif ($cat -eq "05-Diagram") { $reason = "Diagram drawio / export drawio" }
    elseif ($cat -eq "01-Dokumen") { $reason = "Dokumen" }
    elseif ($cat -eq "02-Gambar") { $reason = "Gambar" }
    elseif ($cat -eq "04-Arsip") { $reason = "Arsip/kompres" }
    elseif ($cat -eq "06-Kode-Data") { $reason = "Kode/data/script" }
    else { $reason = "Tidak cocok kategori - cek manual"; $needsReview = $true }
    if ($sub -ne "") { $destDir = Join-Path (Join-Path $GroupedBase $cat) $sub }
    else { $destDir = Join-Path $GroupedBase $cat }
    $proposed = Join-Path $destDir $name
  }

  if ($proposed -ne "") {
    if (Test-Path -LiteralPath $proposed) { $conflict = $true }
    $key = $proposed.ToLower()
    if ($destSeen.ContainsKey($key)) { $conflict = $true }
    else { $destSeen[$key] = $true }
  }

  $rows.Add([pscustomobject]@{
    source = (Join-Path $Root $name)
    proposed_dest = $proposed
    category = $cat
    subcategory = $sub
    size_bytes = $size
    size_mb = [math]::Round($size / 1MB, 2)
    modified = $mod
    conflict = $conflict
    suggest_sudah_install = $suggestSudah
    needs_review = $needsReview
    reason = $reason
    setuju_YN = ""
  })
}

$jsonPath = Join-Path $OutputDir ("report-" + $stamp + ".json")
$csvPath = Join-Path $OutputDir ("report-" + $stamp + ".csv")
$htmlPath = Join-Path $OutputDir ("report-" + $stamp + ".html")
$rows | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $jsonPath -Encoding UTF8
$rows | Export-Csv -LiteralPath $csvPath -NoTypeInformation -Encoding UTF8

# --- HTML: bangun string baris per baris (hindari here-string expandable) ---
$htmlLines = New-Object System.Collections.Generic.List[string]
$htmlLines.Add("<!DOCTYPE html><html lang='id'><head><meta charset='utf-8'><title>Rapikan D</title>")
$htmlLines.Add("<style>body{font-family:Segoe UI,Arial;margin:20px}table{border-collapse:collapse;width:100%;font-size:13px}th,td{border:1px solid #ccc;padding:6px}th{background:#f0f0f0}.review{background:#fff3cd}.conflict{background:#f8d7da}</style>")
$htmlLines.Add("</head><body>")
$htmlLines.Add("<h2>Dry-run grouping root D - " + $stamp + " (tidak ada file dipindah)</h2>")
$htmlLines.Add("<p>Total file: " + $rows.Count + ". Isi kolom setuju_YN di CSV untuk review.</p>")
$htmlLines.Add("<table><thead><tr><th>Source</th><th>Proposed Dest</th><th>Kategori</th><th>MB</th><th>Modified</th><th>Conflict</th><th>Saran Sudah-Install</th><th>Needs Review</th><th>Reason</th></tr></thead><tbody>")
foreach ($r in ($rows | Sort-Object category, source)) {
  $cls = ""
  if ($r.needs_review) { $cls = "review" }
  elseif ($r.conflict) { $cls = "conflict" }
  $s = [System.Net.WebUtility]::HtmlEncode([string]$r.source)
  $d = [System.Net.WebUtility]::HtmlEncode([string]$r.proposed_dest)
  $rs = [System.Net.WebUtility]::HtmlEncode([string]$r.reason)
  $catLabel = [string]$r.category
  if ([string]$r.subcategory -ne "") { $catLabel = $catLabel + " / " + [string]$r.subcategory }
  $htmlLines.Add("<tr class='" + $cls + "'><td>" + $s + "</td><td>" + $d + "</td><td>" + $catLabel + "</td><td>" + $r.size_mb + "</td><td>" + $r.modified + "</td><td>" + $r.conflict + "</td><td>" + $r.suggest_sudah_install + "</td><td>" + $r.needs_review + "</td><td>" + $rs + "</td></tr>")
}
$htmlLines.Add("</tbody></table></body></html>")
[System.IO.File]::WriteAllLines($htmlPath, $htmlLines, [System.Text.Encoding]::UTF8)

$byCat = $rows | Group-Object { if ($_.category) { $_.category } else { "(skip)" } } | Sort-Object Name
Write-Output ("REPORT_JSON=" + $jsonPath)
Write-Output ("REPORT_CSV=" + $csvPath)
Write-Output ("REPORT_HTML=" + $htmlPath)
Write-Output ("TOTAL=" + $rows.Count)
foreach ($g in $byCat) {
  $sum = ($g.Group | Measure-Object size_bytes -Sum).Sum
  $mb = [math]::Round($sum / 1MB, 1)
  Write-Output ("CAT " + $g.Name + " count=" + $g.Count + " mb=" + $mb)
}
$nr = ($rows | Where-Object { $_.needs_review }).Count
$cf = ($rows | Where-Object { $_.conflict }).Count
$sg = ($rows | Where-Object { $_.suggest_sudah_install }).Count
Write-Output ("NEEDS_REVIEW=" + $nr)
Write-Output ("CONFLICT=" + $cf)
Write-Output ("SUGGEST_SUDAH_INSTALL=" + $sg)
