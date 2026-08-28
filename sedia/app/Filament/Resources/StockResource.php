<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockResource\Pages;
use App\Models\Stock;
use App\Support\OutletContext;
use App\Support\RolePermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class StockResource extends Resource
{
    protected static ?string $model = Stock::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|UnitEnum|null $navigationGroup = 'Persediaan';

    protected static ?string $navigationLabel = 'Saldo Stok';

    protected static ?int $navigationSort = 2;

    protected static ?string $pluralModelLabel = 'Saldo Stok';

    public static function canViewAny(): bool
    {
        return RolePermission::can(OutletContext::user(), 'StockResource', 'view');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return OutletContext::visibleQuery(parent::getEloquentQuery());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('outlet.name')->label('Outlet')->sortable()->searchable(),
                TextColumn::make('ingredient.name')->label('Bahan Baku')->sortable()->searchable(),
                TextColumn::make('ingredient.unit')->label('Satuan')->badge(),
                TextColumn::make('quantity')
                    ->label('Saldo')
                    ->numeric(3)
                    ->sortable()
                    ->color(fn (Stock $record): string => $record->isBelowMinimum() ? 'danger' : 'success'),
                TextColumn::make('ingredient.min_stock')->label('Min. Stok')->numeric(3),
                TextColumn::make('updated_at')->label('Update Terakhir')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('outlet_id')->label('Outlet')->relationship('outlet', 'name'),
            ])
            ->striped()
            ->searchPlaceholder('Cari stok...')
            ->emptyStateHeading('Belum ada stok')
            ->emptyStateDescription('Saldo stok terbentuk dari pembelian & penjualan. Cek transfer stok.')
            ->emptyStateIcon('heroicon-o-archive-box')
            ->emptyStateActions([
                CreateAction::make()->label('Stok'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => OutletContext::user()?->isAdmin() ?? false),
                ]),
            ])
            ->defaultSort('outlet_id')
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListStocks::route('/')];
    }
}
