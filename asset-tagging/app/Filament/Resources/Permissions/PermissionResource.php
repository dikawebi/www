<?php

namespace App\Filament\Resources\Permissions;

use Spatie\Permission\Models\Permission;
use Filament\Forms\Components\TextInput;
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
class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Akses';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Kunci Hak Akses')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Izin (Permission)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Contoh: create_assets, edit_status'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Kode Izin')
                    ->searchable(),
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
            'index' => \App\Filament\Resources\Permissions\Pages\ListPermissions::route('/'),
            'create' => \App\Filament\Resources\Permissions\Pages\CreatePermission::route('/create'),
            'edit' => \App\Filament\Resources\Permissions\Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
