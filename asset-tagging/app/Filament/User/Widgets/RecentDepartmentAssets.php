<?php

namespace App\Filament\User\Widgets;

use App\Models\Asset;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentDepartmentAssets extends BaseWidget
{
    protected static ?string $heading = '5 Aset Terbaru di Sistem';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Mengambil 5 aset terbaru yang baru masuk tanpa filter departemen
                Asset::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('asset_id')
                    ->label('ID Aset')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Barang')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'In use' => 'success',
                        'Broke' => 'danger',
                        'Idle' => 'warning',
                        default => 'gray',
                    }),
            ]);
    }
}
