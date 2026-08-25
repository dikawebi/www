<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Support\OutletContext;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public static function canEdit($record): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public static function canDelete($record): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required()->maxLength(255),
            TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true)->maxLength(255),
            TextInput::make('password')
                ->label('Password')
                ->password()
                ->revealable()
                ->required(fn ($record) => $record === null)
                ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                ->dehydrated(fn ($state) => filled($state))
                ->helperText('Kosongkan jika tidak ingin mengubah password'),
            Select::make('role')
                ->label('Role')
                ->options(['admin' => 'Administrator', 'staff' => 'Staff Outlet'])
                ->default('staff')
                ->required()
                ->live(),
            Select::make('outlet_id')
                ->label('Outlet')
                ->options(OutletContext::selectableOutletOptions())
                ->searchable()
                ->preload()
                ->visible(fn ($get) => $get('role') === 'staff')
                ->default(fn () => OutletContext::defaultOutletId())
                ->required(fn ($get) => $get('role') === 'staff')
                ->helperText('Outlet yang bisa diakses staff ini'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('role')->label('Role')->badge()->color(fn ($state) => $state === 'admin' ? 'danger' : 'info'),
                TextColumn::make('outlet.name')->label('Outlet')->default('(Semua)'),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y')->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
