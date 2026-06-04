<?php

namespace App\Filament\Resources\AssetSequences\Pages;

use App\Filament\Resources\AssetSequences\AssetSequenceResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

class CreateAssetSequence extends CreateRecord
{
    protected static string $resource = AssetSequenceResource::class;

    protected function makeSchema(): Schema
    {
        return $this->getResource()::schema(
            parent::makeSchema()->operation('create')
        );
    }
}
