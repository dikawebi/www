<?php

namespace App\Filament\Resources\FuelConsumptions\Pages;

use App\Filament\Resources\FuelConsumptions\FuelConsumptionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFuelConsumption extends ViewRecord
{
    protected static string $resource = FuelConsumptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
