<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OutletResource\Pages;
use App\Models\Outlet;
use App\Models\User;
use BackedEnum;
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

class OutletResource extends Resource
{
    protected static ?string $model = Outlet::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Outlet';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->isAdmin() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->isAdmin() ?? false;
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
                ->label('Nama outlet')
                ->required()
                ->maxLength(255),
            TextInput::make('address')
                ->label('Alamat')
                ->maxLength(255),
            TextInput::make('phone')
                ->label('No. telepon')
                ->tel()
                ->maxLength(20),
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
                    ->label('Nama outlet')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label('Telepon'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('employees_count')
                    ->label('Karyawan')
                    ->counts('employees'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status aktif'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOutlets::route('/'),
            'create' => Pages\CreateOutlet::route('/create'),
            'edit' => Pages\EditOutlet::route('/{record}/edit'),
        ];
    }
}
