<?php

namespace App\Filament\Resources\AccountManagement\Pages;

use App\Filament\Resources\AccountManagement\AccountManagementResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAccountManagement extends ViewRecord
{
    protected static string $resource = AccountManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
