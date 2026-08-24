<?php

namespace App\Filament\Resources\StockTransferResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\Stock;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Item Transfer';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('ingredient_id')
                ->label('Bahan Baku')
                ->relationship('ingredient', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->reactive()
                ->afterStateUpdated(fn ($state, $set, $livewire) => 
                    $set('available_stock', Stock::where('ingredient_id', $state)
                        ->where('outlet_id', $livewire->ownerRecord->from_outlet_id)
                        ->value('quantity') ?? 0)
                ),
            TextInput::make('available_stock')
                ->label('Stok Tersedia di Outlet Asal')
                ->disabled()
                ->numeric(),
            TextInput::make('quantity')
                ->label('Jumlah yang akan di-transfer')
                ->numeric()
                ->required()
                ->minValue(0.001)
                ->maxValue(fn (Get $get) => $get('available_stock')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ingredient.name')
            ->columns([
                TextColumn::make('ingredient.name')->label('Bahan Baku'),
                TextColumn::make('ingredient.unit')->label('Satuan')->badge(),
                TextColumn::make('quantity')->label('Jumlah')->numeric(3),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
