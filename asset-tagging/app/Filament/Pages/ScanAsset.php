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

    // Mendaftarkan halaman kamera agar aktif baik di panel admin maupun panel user
    protected static array $panels = ['admin', 'user'];

    protected string $view = 'filament.pages.scan-asset';

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
                ->title('Aset Berhasil Terdeteksi!')
                ->body("Membuka data: {$asset->name}")
                ->success()
                ->send();

            // Membaca kondisi panel yang sedang aktif diakses saat ini
            $currentPanel = filament()->getCurrentPanel()->getId();

            if ($currentPanel === 'user') {
                // UPDATE: Jika diakses dari panel user, lempar ke rute UserViewResource kustom
                return redirect()->to(
                    \App\Filament\User\Resources\UserViewResource::getUrl('view', ['record' => $asset->id])
                );
            }

            // Jika diakses dari panel admin, biarkan masuk ke detail administrator asli Anda
            return redirect()->to(
                \App\Filament\Resources\Assets\AssetResource::getUrl('view', ['record' => $asset->id])
            );

        } else {
            Notification::make()
                ->title('Aset Tidak Terdaftar')
                ->body("ID Aset ({$assetId}) gagal ditemukan dalam sistem database.")
                ->danger()
                ->send();
        }
    }
}
