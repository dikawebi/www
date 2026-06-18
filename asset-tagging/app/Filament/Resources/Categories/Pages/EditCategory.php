<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

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
