<?php

namespace App\Filament\Resources\Departments;

use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Resources\Departments\Pages\EditDepartment;
use App\Filament\Resources\Departments\Pages\ListDepartments;
use App\Filament\Resources\Departments\Pages\ViewDepartment;
//use App\Filament\Resources\Departments\Schemas\DepartmentForm;
use App\Filament\Resources\Departments\Schemas\DepartmentInfolist;
//use App\Filament\Resources\Departments\Tables\DepartmentsTable;
use App\Models\Department;
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
class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Department';

    public static function form(Schema $schema): Schema
    {
        //return DepartmentForm::configure($schema);

        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Department Name')
                    ->required()
                    ->unique(ignoreRecord: true),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DepartmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
       // return DepartmentsTable::configure($table);
       return $table
            ->columns([
            TextColumn::make('name')->label('Department Name')->sortable()->searchable(),
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

    public static function canViewAny(): bool { /** @var \App\Models\User|null $user */ $user = Auth::user(); return $user?->can('view_departments') ?? false; }
    public static function canCreate(): bool { /** @var \App\Models\User|null $user */ $user = Auth::user(); return $user?->can('create_departments') ?? false; }
    public static function canEdit(Model $record): bool { /** @var \App\Models\User|null $user */ $user = Auth::user(); return $user?->can('edit_departments') ?? false; }
    public static function canDelete(Model $record): bool { /** @var \App\Models\User|null $user */ $user = Auth::user(); return $user?->can('delete_departments') ?? false; }

    public static function getPages(): array
    {
        return [
            'index' => ListDepartments::route('/'),
            'create' => CreateDepartment::route('/create'),
            'view' => ViewDepartment::route('/{record}'),
            'edit' => EditDepartment::route('/{record}/edit'),
        ];
    }
}
