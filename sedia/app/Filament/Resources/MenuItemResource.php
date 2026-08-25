<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuItemResource\Pages;
use App\Filament\Resources\MenuItemResource\RelationManagers\RecipesRelationManager;
use App\Models\MenuItem;
use App\Support\OutletContext;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|UnitEnum|null $navigationGroup = 'Produk';

    protected static ?string $navigationLabel = 'Menu';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return OutletContext::user()?->isAdmin() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return OutletContext::user()?->isAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return OutletContext::user()?->isAdmin() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return OutletContext::user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama menu')
                ->required()
                ->maxLength(255),
            TextInput::make('category')
                ->label('Kategori')
                ->maxLength(255)
                ->helperText('Contoh: Ayam, Minuman, Snack'),
            TextInput::make('price')
                ->label('Harga jual')
                ->numeric()
                ->prefix('Rp')
                ->required(),
            Toggle::make('is_active')
                ->label('Aktif dijual')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama menu')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge(),
                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR'),
                TextColumn::make('recipes_count')
                    ->label('Jumlah bahan')
                    ->counts('recipes')
                    ->description('Bahan di resep'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status aktif'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => OutletContext::user()?->isAdmin() ?? false),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            RecipesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenuItems::route('/'),
            'create' => Pages\CreateMenuItem::route('/create'),
            'edit' => Pages\EditMenuItem::route('/{record}/edit'),
        ];
    }
}
