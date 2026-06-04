<?php

namespace App\Filament\Resources\AssetSequences\Pages;

use App\Filament\Resources\AssetSequences\AssetSequenceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssetSequence extends EditRecord
{
    protected static string $resource = AssetSequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function makeSchema(): \Filament\Schemas\Schema
    {
        return $this->getResource()::schema(
            parent::makeSchema()->operation('edit')
        );
    }
}
