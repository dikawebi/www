<?php

namespace App\Filament\Widgets;

use App\Models\Stock;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class LowStockTableWidget extends TableWidget
{
    protected static ?string $heading = 'Stok Kritis';

    public function table(Table $table): Table
    {
        /** @var User|null $user */
        $user = Auth::user();
        $query = Stock::query()->with(['outlet', 'ingredient'])->whereHas('ingredient', function ($q) {
            $q->whereColumn('stocks.quantity', '<=', 'ingredients.min_stock');
        });

        if ($user && ! $user->isAdmin() && $user->outlet_id) {
            $query->where('outlet_id', $user->outlet_id);
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('outlet.name')->label('Outlet'),
                TextColumn::make('ingredient.name')->label('Bahan'),
                TextColumn::make('quantity')->label('Stok'),
            ]);
    }
}
