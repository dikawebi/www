<?php

namespace App\Filament\Resources\AssetSequences\Pages;

use App\Filament\Resources\AssetSequences\AssetSequenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssetSequences extends ListRecords
{
    /**
     * Page to list Asset Sequences in Filament.
     */
    protected static string $resource = AssetSequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
