<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers\TransactionsRelationManager;
use App\Models\Employee;
use App\Support\OutletContext;
use App\Support\RolePermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Karyawan';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return RolePermission::can(OutletContext::user(), 'EmployeeResource', 'create');
    }

    public static function canEdit(Model $record): bool
    {
        $user = OutletContext::user();

        if (! RolePermission::can($user, 'EmployeeResource', 'edit')) {
            return false;
        }

        return $user?->isAdmin() || $record->outlet_id === $user?->outlet_id;
    }

    public static function canDelete(Model $record): bool
    {
        return RolePermission::can(OutletContext::user(), 'EmployeeResource', 'delete');
    }

    public static function getEloquentQuery(): Builder
    {
        return OutletContext::visibleQuery(parent::getEloquentQuery());
    }

    public static function form(Schema $schema): Schema
    {
        /** @var User $user */
        $user = Auth::user();
        $isAdmin = $user?->isAdmin() ?? false;

        return $schema->components([
            Select::make('outlet_id')
                ->label('Outlet')
                ->options(OutletContext::selectableOutletOptions())
                ->searchable()
                ->preload()
                ->default(fn () => OutletContext::defaultOutletId())
                ->required()
                ->disabled(! $isAdmin)
                ->dehydrated(true)
                ->rules([
                    function () {
                        return function (string $attribute, $value, \Closure $fail) {
                            if (filled($value) && ! array_key_exists((int) $value, OutletContext::selectableOutletOptions())) {
                                $fail('Outlet tidak valid untuk akun Anda.');
                            }
                            if (! OutletContext::user()?->isAdmin() && ! OutletContext::currentOutletId()) {
                                $fail('Akun Anda belum terhubung ke outlet.');
                            }
                        };
                    },
                ]),
            TextInput::make('name')->label('Nama')->required()->maxLength(255),
            TextInput::make('phone')->label('No. kontak')->tel()->maxLength(20),
            TextInput::make('position')->label('Posisi/jabatan')->maxLength(255),
            TextInput::make('base_salary')->label('Gaji pokok')->numeric()->prefix('Rp')->default(0)->disabled(! $isAdmin)->dehydrated(true),
            DatePicker::make('join_date')->label('Tanggal bergabung'),
            Select::make('status')
                ->label('Status')
                ->options(['active' => 'Aktif', 'resigned' => 'Resign'])
                ->default('active')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('outlet.name')->label('Outlet')->sortable(),
                TextColumn::make('position')->label('Posisi'),
                TextColumn::make('base_salary')->label('Gaji pokok')->money('IDR'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'resigned' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('outlet_id')->label('Outlet')->relationship('outlet', 'name'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(['active' => 'Aktif', 'resigned' => 'Resign']),
            ])
            ->striped()
            ->searchPlaceholder('Cari karyawan...')
            ->emptyStateHeading('Belum ada karyawan')
            ->emptyStateDescription('Tambah karyawan pertama untuk kelola data SDM outlet.')
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateActions([
                CreateAction::make()->url(fn () => static::getUrl('create'))->label('Tambah Karyawan'),
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
        return [TransactionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
