# Approve-Report.ps1 - Tandai setuju_YN=Y untuk kategori yang disetujui user.
# Backup CSV asli dulu. Hanya baris yang punya proposed_dest yang ditandai.
param(
  [string]$ReportCsv = "D:\_Grouped\_report\report-20260903-165649.csv"
)

$bak = $ReportCsv -replace '\.csv$', '.orig.csv'
Copy-Item -LiteralPath $ReportCsv -Destination $bak -Force
Write-Output ("BACKUP=" + $bak)

$rows = Import-Csv -LiteralPath $ReportCsv
$n = 0
foreach ($r in $rows) {
  if ([string]::IsNullOrWhiteSpace($r.proposed_dest)) { continue }
  $r.setuju_YN = "Y"
  $n++
}
$rows | Export-Csv -LiteralPath $ReportCsv -NoTypeInformation -Encoding UTF8
Write-Output ("APPROVED_SET=" + $n)
Write-Output ("TOTAL=" + $rows.Count)
