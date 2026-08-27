<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayrollResource\Pages;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use App\Support\OutletContext;
use App\Support\RolePermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class PayrollResource extends Resource
{
    protected static ?string $model = Payroll::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Penggajian';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Penggajian';

    protected static ?string $pluralModelLabel = 'Penggajian';

    public static function canCreate(): bool
    {
        return RolePermission::can(OutletContext::user(), 'PayrollResource', 'create');
    }

    public static function canEdit(Model $record): bool
    {
        $user = OutletContext::user();

        if (! RolePermission::can($user, 'PayrollResource', 'edit')) {
            return false;
        }

        if ($record->status === 'paid') {
            return false;
        }

        return $user?->isAdmin() || $record->outlet_id === $user?->outlet_id;
    }

    public static function canDelete(Model $record): bool
    {
        return RolePermission::can(OutletContext::user(), 'PayrollResource', 'delete');
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
                ->live()
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
                        };
                    },
                ])
                ->afterStateUpdated(fn ($set) => $set('employee_id', null)),
            Select::make('employee_id')
                ->label('Nama')
                ->options(function (Get $get) {
                    $outletId = $get('outlet_id');

                    return Employee::query()
                        ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
                        ->where('status', 'active')
                        ->orderBy('name')
                        ->pluck('name', 'id');
                })
                ->searchable()
                ->preload()
                ->required(),
            DatePicker::make('pay_date')
                ->label('Tanggal Gajian')
                ->required()
                ->default(now()),
            DatePicker::make('period_start')
                ->label('Periode Mulai')
                ->required(),
            DatePicker::make('period_end')
                ->label('Periode Akhir')
                ->required(),
            TextInput::make('base_salary')
                ->label('Gaji Pokok')
                ->numeric()
                ->prefix('Rp')
                ->live()
                ->default(0)
                ->required(),
            TextInput::make('bonus_masuk')
                ->label('Bonus Masuk')
                ->numeric()
                ->prefix('Rp')
                ->live()
                ->default(0)
                ->required(),
            TextInput::make('bonus_goreng')
                ->label('Bonus Goreng')
                ->numeric()
                ->prefix('Rp')
                ->live()
                ->default(0)
                ->required(),
            TextInput::make('kasbon_deduction')
                ->label('Kasbon')
                ->helperText('Jumlah kasbon yang dipotong pada periode gajian ini.')
                ->numeric()
                ->prefix('Rp')
                ->live()
                ->default(0)
                ->required(),

            // Preview saja di layar. Nilai final total_salary yang tersimpan ke DB
            // selalu dihitung ulang di server lewat Payroll::booted() (static::saving),
            // supaya konsisten walau field ini tidak sempat ke-render ulang.
            Placeholder::make('total_salary_preview')
                ->label('Total Gaji (preview)')
                ->content(function (Get $get): string {
                    $total = (float) ($get('base_salary') ?: 0)
                        + (float) ($get('bonus_masuk') ?: 0)
                        + (float) ($get('bonus_goreng') ?: 0)
                        - (float) ($get('kasbon_deduction') ?: 0);

                    return 'Rp '.number_format($total, 0, ',', '.');
                }),

            Select::make('status')
                ->label('Status')
                ->options([
                    'draft' => 'Draft',
                    'paid' => 'Dibayar',
                    'cancelled' => 'Dibatalkan',
                ])
                ->default('draft')
                ->required()
                ->disabled(fn (?Payroll $record) => $record?->status === 'paid')
                ->dehydrated(true),
            Textarea::make('note')
                ->label('Catatan')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('outlet.name')
                    ->label('Outlet')
                    ->sortable(),
                TextColumn::make('employee.name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pay_date')
                    ->label('Tanggal Gajian')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('period_start')
                    ->label('Periode Mulai')
                    ->date('d M Y'),
                TextColumn::make('period_end')
                    ->label('Periode Akhir')
                    ->date('d M Y'),
                TextColumn::make('base_salary')
                    ->label('Gaji Pokok')
                    ->money('IDR'),
                TextColumn::make('bonus_masuk')
                    ->label('Bonus Masuk')
                    ->money('IDR'),
                TextColumn::make('bonus_goreng')
                    ->label('Bonus Goreng')
                    ->money('IDR'),
                TextColumn::make('kasbon_deduction')
                    ->label('Kasbon')
                    ->money('IDR'),
                TextColumn::make('total_salary')
                    ->label('Total Gaji')
                    ->money('IDR')
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'draft' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('note')
                    ->label('Catatan')
                    ->limit(30)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('outlet_id')->label('Outlet')->relationship('outlet', 'name'),
                SelectFilter::make('employee_id')->label('Nama')->relationship('employee', 'name'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'paid' => 'Dibayar',
                        'cancelled' => 'Dibatalkan',
                    ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => OutletContext::user()?->isAdmin() ?? false),
                ]),
            ])
            ->defaultSort('period_start', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrolls::route('/'),
            'create' => Pages\CreatePayroll::route('/create'),
            'edit' => Pages\EditPayroll::route('/{record}/edit'),
        ];
    }
}
