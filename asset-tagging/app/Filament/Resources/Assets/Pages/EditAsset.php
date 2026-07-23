<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAsset extends EditRecord
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        // Ini akan mengarahkan kembali ke halaman 'index' (ListAssets)
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationMessage(): ?string
{
    return 'Data aset berhasil disimpan!';
}
}
