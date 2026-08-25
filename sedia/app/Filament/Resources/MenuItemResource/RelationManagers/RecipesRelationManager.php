<?php

namespace App\Filament\Resources\MenuItemResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RecipesRelationManager extends RelationManager
{
    protected static string $relationship = 'recipes';

    protected static ?string $title = 'Resep / BOM';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('ingredient_id')
                ->label('Bahan baku')
                ->relationship('ingredient', 'name')
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('qty_per_unit')
                ->label('Qty per porsi')
                ->numeric()
                ->required()
                ->helperText('Jumlah bahan baku yang dipakai untuk 1 porsi menu ini'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ingredient.name')
            ->columns([
                TextColumn::make('ingredient.name')
                    ->label('Bahan baku')
                    ->sortable(),
                TextColumn::make('ingredient.unit')
                    ->label('Satuan')
                    ->badge(),
                TextColumn::make('qty_per_unit')
                    ->label('Qty per porsi')
                    ->numeric(4),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
