<?php
namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

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
