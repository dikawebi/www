<?php

namespace App\Filament\Resources\AssetSequences\Pages;

use App\Filament\Resources\AssetSequences\AssetSequenceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAssetSequence extends ViewRecord
{
    protected static string $resource = AssetSequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
