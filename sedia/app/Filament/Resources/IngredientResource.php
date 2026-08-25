<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IngredientResource\Pages;
use App\Models\Ingredient;
use App\Models\User;
use App\Support\OutletContext;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class IngredientResource extends Resource
{
    protected static ?string $model = Ingredient::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|UnitEnum|null $navigationGroup = 'Persediaan';

    protected static ?string $navigationLabel = 'Bahan baku';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return true;
    }

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
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama bahan baku')
                ->required()
                ->maxLength(255),
            Select::make('unit')
                ->label('Satuan')
                ->options([
                    'kg' => 'Kilogram (kg)',
                    'gram' => 'Gram',
                    'liter' => 'Liter',
                    'ml' => 'Mililiter (ml)',
                    'pcs' => 'Pieces (pcs)',
                    'porsi' => 'Porsi',
                ])
                ->required(),
            TextInput::make('cost_per_unit')
                ->label('Harga beli / unit')
                ->numeric()
                ->prefix('Rp')
                ->default(0)
                ->disabled(! (OutletContext::user()?->isAdmin() ?? false))
                ->dehydrated(true)
                ->helperText('Harga beli per satuan (sama dengan Satuan di atas). Dipakai untuk hitung HPP & margin menu.'),
            TextInput::make('min_stock')
                ->label('Stock minimum (reorder point)')
                ->numeric()
                ->default(0)
                ->disabled(! (OutletContext::user()?->isAdmin() ?? false))
                ->dehydrated(true)
                ->helperText('Sistem akan tandai stock rendah kalau quantity di bawah angka ini'),
            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama bahan baku')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit')
                    ->label('Satuan')
                    ->badge(),
                TextColumn::make('cost_per_unit')
                    ->label('Harga beli/unit')
                    ->money('IDR'),
                TextColumn::make('min_stock')
                    ->label('Min. stock')
                    ->numeric(3),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIngredients::route('/'),
            'create' => Pages\CreateIngredient::route('/create'),
            'edit' => Pages\EditIngredient::route('/{record}/edit'),
        ];
    }
}
