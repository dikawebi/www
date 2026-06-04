<?php

namespace App\Filament\Resources\AssetSequences\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AssetSequenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                TextInput::make('prefix')
                    ->required(),
                TextInput::make('format')
                    ->required()
                    ->default('{prefix}-{year}-{sequence}'),
                TextInput::make('next_value')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('padding')
                    ->required()
                    ->numeric()
                    ->default(4),
            ]);
    }
}
