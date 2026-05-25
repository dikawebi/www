<?php

namespace App\Filament\Resources\FuelConsumptions\Pages;

use App\Filament\Resources\FuelConsumptions\FuelConsumptionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFuelConsumption extends EditRecord
{
    protected static string $resource = FuelConsumptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
