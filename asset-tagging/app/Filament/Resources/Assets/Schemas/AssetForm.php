<?php

namespace App\Filament\Resources\Assets\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('asset_id')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                Select::make('location_id')
                    ->relationship('location', 'name')
                    ->required(),
                Select::make('department_id')
                    ->relationship('department', 'name')
                    ->required(),
                TextInput::make('pr_number'),
                TextInput::make('po_number'),
                TextInput::make('user_name'),
                TextInput::make('status')
                    ->required()
                    ->default('Idle'),
                Textarea::make('images')
                    ->columnSpanFull(),
            ]);
    }
}
