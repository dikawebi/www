<?php

namespace App\Filament\Resources\StockOpnameResource\RelationManagers;

use App\Models\Stock;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
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
                ->dehydrated(true)
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
        $isDraft = fn () => $this->getOwnerRecord()?->status === 'draft';

        return $table
            ->recordTitleAttribute('ingredient.name')
            ->headerActions([
                CreateAction::make()->visible($isDraft),
                Action::make('populateAll')->visible($isDraft)
                    ->label('Tarik Semua Item')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->modalHeading('Pilih Item untuk Opname')
                    ->form([
                        Repeater::make('items_to_opname')
                            ->label('Item Tersedia')
                            ->schema([
                                Hidden::make('ingredient_id'),
                                TextInput::make('ingredient_name')
                                    ->label('Bahan Baku')
                                    ->disabled(),
                                TextInput::make('system_qty')
                                    ->label('Qty Sistem'),
                                // ->hidden(),
                                Hidden::make('system_qty'),
                                TextInput::make('actual_qty')
                                    ->label('Qty Aktual')
                                    ->numeric()
                                    ->default(0),
                            ])
                            ->deletable(false)
                            ->addable(false)
                            ->columns(4),
                    ])
                    ->fillForm(function ($livewire) {
                        $outletId = $livewire->ownerRecord->outlet_id;
                        $stocks = Stock::where('outlet_id', $outletId)
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
                                'system_qty' => (float) $stock->quantity,
                                'actual_qty' => $existingItem ? (float) $existingItem->actual_qty : (float) $stock->quantity,
                            ];
                        }

                        return ['items_to_opname' => $items];
                    })
                    ->action(function (array $data, $livewire) {
                        if (! isset($data['items_to_opname'])) {
                            Notification::make()->title('Data tidak valid')->danger()->send();

                            return;
                        }
                        foreach ($data['items_to_opname'] as $item) {
                            $livewire->ownerRecord->items()->updateOrCreate(
                                ['ingredient_id' => $item['ingredient_id']],
                                [
                                    'system_qty' => $item['system_qty'] ?? 0,
                                    'actual_qty' => $item['actual_qty'] ?? 0,
                                ]
                            );
                        }
                        Notification::make()->title('Item berhasil ditambahkan')->success()->send();
                    }),
            ])
            ->columns([
                TextColumn::make('ingredient.name')->label('Bahan Baku')->sortable(),
                TextColumn::make('ingredient.unit')->label('Satuan')->badge(),
                TextColumn::make('system_qty')->label('Qty Sistem')->numeric(3),
                TextColumn::make('actual_qty')->label('Qty Aktual')->numeric(3),
                TextColumn::make('difference')->label('Selisih')->numeric(3)
                    ->color(fn ($state) => (float) $state > 0 ? 'success' : ((float) $state < 0 ? 'danger' : 'gray')),
            ])
        //    ->headerActions([CreateAction::make()])
            ->recordActions([
                EditAction::make()->visible($isDraft),
                DeleteAction::make()->visible($isDraft),
            ]);
    }
}
