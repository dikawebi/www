<?php

namespace App\Filament\Resources\StockTransferResource\RelationManagers;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Set;
use App\Models\Stock;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
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
            ->headerActions([
                CreateAction::make(),
                Action::make('populateAll')
                    ->label('Tarik Semua Item')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->modalHeading('Pilih Item untuk Ditransfer')
                    ->form([
                        \Filament\Forms\Components\Repeater::make('items_to_transfer')
                            ->label('Item Tersedia')
                            ->schema([
                                \Filament\Forms\Components\Hidden::make('ingredient_id'),
                                \Filament\Forms\Components\TextInput::make('ingredient_name')
                                    ->label('Bahan Baku')
                                    ->disabled(),
                                \Filament\Forms\Components\TextInput::make('available_stock')
                                    ->label('Stok Tersedia')
                                    ->disabled(),
                                \Filament\Forms\Components\TextInput::make('transfer_qty')
                                    ->label('Qty Transfer')
                                    ->numeric()
                                    ->default(0),
                            ])
                            ->deletable(false)
                            ->addable(false)
                            ->columns(4)
                    ])
                    ->mutateFormDataUsing(function ($data) {
                        return $data;
                    })
                    ->fillForm(function ($livewire) {
                        $fromOutletId = $livewire->ownerRecord->from_outlet_id;
                        $stocks = Stock::where('outlet_id', $fromOutletId)
                            ->where('quantity', '>', 0)
                            ->with('ingredient')
                            ->get();

                        $items = [];
                        foreach ($stocks as $stock) {
                            $existingItem = $livewire->ownerRecord->items()
                                ->where('ingredient_id', $stock->ingredient_id)
                                ->first();

                            $items[] = [
                                'ingredient_id' => $stock->ingredient_id,
                                'ingredient_name' => $stock->ingredient->name,
                                'available_stock' => (float)$stock->quantity,
                                'transfer_qty' => $existingItem ? (float)$existingItem->quantity : 0,
                            ];
                        }
                        
                        return [
                            'items_to_transfer' => $items
                        ];
                    })
                    ->action(function (array $data, $livewire) {
                        foreach ($data['items_to_transfer'] as $item) {
                            if ($item['transfer_qty'] > 0) {
                                $livewire->ownerRecord->items()->updateOrCreate(
                                    ['ingredient_id' => $item['ingredient_id']],
                                    ['quantity' => $item['transfer_qty']]
                                );
                            }
                        }
                        Notification::make()->title('Item berhasil ditambahkan')->success()->send();
                    }),
            ])
            ->columns([
                TextColumn::make('ingredient.name')->label('Bahan Baku'),
                TextColumn::make('ingredient.unit')->label('Satuan')->badge(),
                TextColumn::make('quantity')->label('Jumlah')->numeric(3),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
