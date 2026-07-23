<?php

namespace App\Filament\Resources\AccountManagement;

use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select; // 1. Import Select
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use BackedEnum;
use UnitEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; // Tambahkan ini

class AccountManagementResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'User Management';

    protected static ?string $slug = 'user-managements';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Akses';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Akun & Peran Kontrol')
                    ->schema([
                        TextInput::make('name')->label('Nama Pengguna')->required(),
                        TextInput::make('email')->label('Email Login')->email()->required()->unique(ignoreRecord: true),

                        // 2. Tambahkan Select Departemen di sini
                        Select::make('department_id')
                            ->label('Departemen')
                            ->relationship('department', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        TextInput::make('password')
                            ->label('Password Baru')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->dehydrated(fn (?string $state): bool => filled($state)),

                        CheckboxList::make('roles')
                            ->label('Peran / Role Akses')
                            ->relationship('roles', 'name')
                            ->bulkToggleable()
                            ->columns(2),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama Lengkap')->searchable()->sortable(),
                TextColumn::make('email')->label('Alamat Email')->searchable(),

                // Tambahkan kolom departemen agar terlihat di tabel
                TextColumn::make('department.name')->label('Departemen')->sortable(),

                TextColumn::make('roles.name')
                    ->label('Role Aktif')
                    ->badge()
                    ->color('warning'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }



    public static function canViewAny(): bool
    {
        /** @var User $user */
        $user = Auth::user();
        return $user ? $user->can('view_users') : false;
    }

    public static function canCreate(): bool
    {
        /** @var User $user */
        $user = Auth::user();
        return $user ? $user->can('create_users') : false;
    }

    public static function canEdit(Model $record): bool
    {
        /** @var User $user */
        $user = Auth::user();
        return $user ? $user->can('edit_users') : false;
    }

    public static function canDelete(Model $record): bool
    {
        /** @var User $user */
        $user = Auth::user();
        return $user ? $user->can('delete_users') : false;
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\AccountManagement\Pages\ListAccountManagement::route('/'),
            'create' => \App\Filament\Resources\AccountManagement\Pages\CreateAccountManagement::route('/create'),
            'edit' => \App\Filament\Resources\AccountManagement\Pages\EditAccountManagement::route('/{record}/edit'),
        ];
    }
}
