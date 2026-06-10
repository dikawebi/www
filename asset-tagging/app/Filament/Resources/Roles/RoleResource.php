<?php

namespace App\Filament\Resources\Roles;

use Spatie\Permission\Models\Role;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
//use Filament\Schemas\Components\Select;
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

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Akses';

    public static function form(Schema $schema): Schema
{
    return $schema
        ->schema([
            Section::make('Grup Jabatan')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Peran (Role)')
                        ->required()
                        ->unique(ignoreRecord: true),

                    // 💡 PERUBAHAN DI SINI: Dari Select menjadi CheckboxList
                    CheckboxList::make('permissions')
                        ->label('Berikan Izin Akses (Permissions)')
                        ->relationship('permissions', 'name')
                        ->searchable() // Tetap bisa dicari jika jumlahnya banyak
                        ->bulkToggleable() // 🌟 Tombol sakti "Pilih Semua / Batal Pilih Semua"
                        ->columns(3) // 🌟 Dibagi menjadi 3 kolom menyamping agar rapi
                        ->gridDirection('row'),
                ])
        ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Peran')
                    ->searchable(),

                TextColumn::make('permissions.name')
                    ->label('Izin Kerja')
                    ->badge()
                    ->color('info'),
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
        return $user ? $user->can('manage_roles_permissions') : false;
    }

    public static function canCreate(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return $user ? $user->can('manage_roles_permissions') : false;
    }

    public static function canEdit(Model $record): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return $user ? $user->can('manage_roles_permissions') : false;
    }

    public static function canDelete(Model $record): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return $user ? $user->can('manage_roles_permissions') : false;
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\Roles\Pages\ListRoles::route('/'),
            'create' => \App\Filament\Resources\Roles\Pages\CreateRole::route('/create'),
            'edit' => \App\Filament\Resources\Roles\Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
