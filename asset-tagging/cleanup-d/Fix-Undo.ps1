# Fix-Undo.ps1 - Hapus entri bogus elevok dari undo log (gagal pindah karena in-use)
param(
  [string]$UndoJson = "D:\_Grouped\_report\undo\undo-20260903-170226.json",
  [string]$RestorePs = "D:\_Grouped\_report\undo\Restore-20260903-170226.ps1"
)
$moves = Get-Content -LiteralPath $UndoJson -Raw | ConvertFrom-Json
$kept = @($moves | Where-Object { $_.source -notlike "*elevoc_dnn_kernel.log" })
$removed = $moves.Count - $kept.Count
$kept | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $UndoJson -Encoding UTF8
$lines = Get-Content -LiteralPath $RestorePs
$keptLines = @($lines | Where-Object { $_ -notlike "*elevoc_dnn_kernel.log*" })
[System.IO.File]::WriteAllLines($RestorePs, $keptLines, [System.Text.Encoding]::UTF8)
Write-Output ("REMOVED_BOGUS=" + $removed)
Write-Output ("UNDO_VALID_ENTRIES=" + $kept.Count)
