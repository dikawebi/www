<?php

namespace App\Filament\Resources\AccountManagement\Pages;

use App\Filament\Resources\AccountManagement\AccountManagementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccountManagement extends ListRecords
{
    protected static string $resource = AccountManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
