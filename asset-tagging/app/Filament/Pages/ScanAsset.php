<?php

namespace App\Filament\Pages;

use App\Models\Asset;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use BackedEnum;

class ScanAsset extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';
    protected static ?string $navigationLabel = 'Scan QR Asset';
    protected static ?string $title = 'Scanner Kamera Aset';

    // PERBAIKAN: Hapus kata 'static' di sini
    protected string $view = 'filament.pages.scan-asset';

    /**
     * Fungsi untuk memproses teks hasil scan dari JavaScript
     */
    public function checkAsset($scannedText)
    {
        $lines = explode("\n", $scannedText);
        $idLine = $lines[0] ?? '';

        if (str_contains($idLine, ':')) {
            $assetId = trim(explode(':', $idLine)[1]);
        } else {
            $assetId = trim($scannedText);
        }

        $asset = Asset::where('asset_id', $assetId)->first();

        if ($asset) {
            Notification::make()
                ->title('Aset Ditemukan')
                ->success()
                ->send();

            return redirect()->to('/admin/assets/' . $asset->id . '/edit');
        } else {
            Notification::make()
                ->title('Aset Tidak Terdaftar')
                ->description("ID Aset ({$assetId}) tidak ditemukan di sistem database.")
                ->danger()
                ->send();
        }
    }
}
