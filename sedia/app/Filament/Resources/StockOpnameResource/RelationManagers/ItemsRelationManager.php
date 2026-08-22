<?php

namespace App\Filament\Resources\StockOpnameResource\RelationManagers;

use App\Models\Stock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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

    protected static ?string $title = 'Item Opname';

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
                ->afterStateUpdated(function ($state, callable $set) {
                    // Isi system_qty dari saldo stok di outlet opname ini
                    $opname = $this->getOwnerRecord();
                    if ($state && $opname->outlet_id) {
                        $stock = Stock::where('outlet_id', $opname->outlet_id)
                            ->where('ingredient_id', $state)
                            ->first();
                        $set('system_qty', $stock ? (float) $stock->quantity : 0);
                    }
                }),
            TextInput::make('system_qty')
                ->label('Qty Sistem')
                ->numeric()
                ->disabled()
                ->default(0),
            TextInput::make('actual_qty')
                ->label('Qty Aktual (fisik)')
                ->numeric()
                ->required()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ingredient.name')
            ->columns([
                TextColumn::make('ingredient.name')->label('Bahan Baku')->sortable(),
                TextColumn::make('ingredient.unit')->label('Satuan')->badge(),
                TextColumn::make('system_qty')->label('Qty Sistem')->numeric(3),
                TextColumn::make('actual_qty')->label('Qty Aktual')->numeric(3),
                TextColumn::make('difference')->label('Selisih')->numeric(3)
                    ->color(fn ($state) => (float) $state > 0 ? 'success' : ((float) $state < 0 ? 'danger' : 'gray')),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
