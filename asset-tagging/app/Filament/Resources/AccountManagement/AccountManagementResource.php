<?php

namespace App\Filament\Resources\AccountManagement;

use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
//use Filament\Forms\Components\Select;
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
class AccountManagementResource extends Resource
{
    // Mengarahkan Resource ini untuk mengelola tabel data Model User Bawaan
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
                    TextInput::make('password')
                    ->label('Password Baru')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')

                    // 💡 TAMBAHKAN BARIS INI: Untuk mengenkripsi password sebelum dilempar ke database
                    ->dehydrateStateUsing(fn (string $state): string => \Illuminate\Support\Facades\Hash::make($state))

                    ->dehydrated(fn (?string $state): bool => filled($state)),

                    // 💡 PERUBAHAN DI SINI: Dari Select menjadi CheckboxList
                    CheckboxList::make('roles')
                        ->label('Peran / Role Akses')
                        ->relationship('roles', 'name')
                        ->bulkToggleable()
                        ->columns(2), // Dibagi 2 kolom agar proporsional dengan form lainnya
                ])->columns(2)
        ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Alamat Email')
                    ->searchable(),

                // Menampilkan badge daftar Role Spatie yang aktif pada user tersebut
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
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return $user ? $user->can('view_users') : false;
    }

    public static function canCreate(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return $user ? $user->can('create_users') : false;
    }

    public static function canEdit(Model $record): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return $user ? $user->can('edit_users') : false;
    }

    public static function canDelete(Model $record): bool
    {
        /** @var \App\Models\User $user */
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
