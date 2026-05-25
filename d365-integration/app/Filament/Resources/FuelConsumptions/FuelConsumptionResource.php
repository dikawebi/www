<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FuelConsumptionResource\Pages;
use App\Models\FuelConsumption;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
//use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class FuelConsumptionResource extends Resource
{
    protected static ?string $model = FuelConsumption::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-funnel';
    protected static ?string $navigationLabel = 'Fuel Consumption';

    public static function table(Table $table): Table
    {
        return $table

            ->deferLoading()
            ->columns([
                TextColumn::make('TransactionDate')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('JournalNumber')
                    ->label('Journal No')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('WBPDTNumber')
                    ->label('Unit (DT)')
                    ->weight('bold')
                    ->color('primary')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('WBPHMFirst')
                    ->label('HM Start')
                    ->numeric(2)
                    ->alignEnd(),

                TextColumn::make('WBPHMEnd')
                    ->label('HM End')
                    ->numeric(2)
                    ->alignEnd(),

                TextColumn::make('InventoryQuantity')
                    ->label('Qty (L)')
                    ->numeric(2)
                    ->color('danger')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('CostAmount')
                    ->label('Total Cost')
                    ->money('USD')
                    ->alignEnd()
                    ->sortable(),
            ])
            ->paginated([100, 250, 500,1000])
            ->defaultPaginationPageOption(100)
            ->filters([
                Filter::make('TransactionDate')
                    ->form([
                        DatePicker::make('from_date')->label('From Date'),
                        DatePicker::make('to_date')->label('To Date'),
                    ])
                    ->query(fn (Builder $query) => $query), // Logic dihandle di List Page
            ])
            ->persistFiltersInSession(); // Opsional: Simpan filter saat refresh
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFuelConsumptions::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Nonaktifkan tombol tambah data
    }

    public static function getRecordRouteKeyName(): ?string
{
    return 'JournalNumber';
}
}
