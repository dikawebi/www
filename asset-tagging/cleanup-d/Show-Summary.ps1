$json = 'D:\_Grouped\_report\report-20260903-165649.json'
$r = Get-Content -LiteralPath $json -Raw | ConvertFrom-Json
Write-Output "=== NEEDS_REVIEW ==="
$r | Where-Object { $_.needs_review } | ForEach-Object { Write-Output (" - " + $_.source + " | " + $_.reason) }
Write-Output ""
Write-Output "=== INSTALLER by size ==="
$r | Where-Object { $_.category -eq '03-Installer' } | Sort-Object size_bytes -Descending | ForEach-Object { Write-Output (" - " + [System.IO.Path]::GetFileName($_.source) + " | " + $_.size_mb + " MB | " + $_.reason) }
Write-Output ""
Write-Output "=== 07-LAINNYA ==="
$r | Where-Object { $_.category -eq '07-Lainnya' } | ForEach-Object { Write-Output (" - " + $_.source) }
