<?php

namespace App\Filament\Resources\Locations;

use App\Filament\Resources\Locations\Pages\CreateLocation;
use App\Filament\Resources\Locations\Pages\EditLocation;
use App\Filament\Resources\Locations\Pages\ListLocations;
use App\Filament\Resources\Locations\Pages\ViewLocation;
//use App\Filament\Resources\Locations\Schemas\LocationForm;
use App\Filament\Resources\Locations\Schemas\LocationInfolist;
//use App\Filament\Resources\Locations\Tables\LocationsTable;
use App\Models\Location;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Location';

    public static function form(Schema $schema): Schema
    {
       // return LocationForm::configure($schema);
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Location Name')
                    ->required()
                    ->unique(ignoreRecord: true),
            ]);

    }

    public static function infolist(Schema $schema): Schema
    {
        return LocationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
       // return LocationsTable::configure($table);
       return $table
            ->columns([
            TextColumn::make('name')->label('Location Name')->sortable()->searchable(),
            ])->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canViewAny(): bool { /** @var \App\Models\User|null $user */ $user = Auth::user(); return $user?->can('view_locations') ?? false; }
    public static function canCreate(): bool { /** @var \App\Models\User|null $user */ $user = Auth::user(); return $user?->can('create_locations') ?? false; }
    public static function canEdit(Model $record): bool { /** @var \App\Models\User|null $user */ $user = Auth::user(); return $user?->can('edit_locations') ?? false; }
    public static function canDelete(Model $record): bool { /** @var \App\Models\User|null $user */ $user = Auth::user(); return $user?->can('delete_locations') ?? false; }

    public static function getPages(): array
    {
        return [
            'index' => ListLocations::route('/'),
            'create' => CreateLocation::route('/create'),
            'view' => ViewLocation::route('/{record}'),
            'edit' => EditLocation::route('/{record}/edit'),
        ];
    }
}
