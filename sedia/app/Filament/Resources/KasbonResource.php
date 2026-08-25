<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KasbonResource\Pages;
use App\Models\Employee;
use App\Models\EmployeeTransaction;
use App\Models\User;
use App\Support\OutletContext;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class KasbonResource extends Resource
{
    protected static ?string $model = EmployeeTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Kasbon';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Kasbon';

    protected static ?string $pluralModelLabel = 'Kasbon';

    public static function canCreate(): bool
    {
        return (bool) Auth::user();
    }

    public static function canEdit(Model $record): bool
    {
        /** @var User $user */
        $user = Auth::user();
        if ($record->status !== 'pending') {
            return $user?->isAdmin() ?? false;
        }

        return $user?->isAdmin() || $record->outlet_id === $user?->outlet_id;
    }

    public static function canDelete(Model $record): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $user?->isAdmin() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return OutletContext::visibleQuery(parent::getEloquentQuery()->where('type', 'kasbon'));
    }

    public static function form(Schema $schema): Schema
    {
        /** @var User $user */
        $user = Auth::user();
        $isAdmin = $user?->isAdmin() ?? false;

        return $schema->components([
            Hidden::make('type')->default('kasbon'),
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
                ->label('Karyawan')
                ->options(function ($get) {
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
            TextInput::make('amount')
                ->label('Nominal Kasbon')
                ->numeric()
                ->prefix('Rp')
                ->minValue(0)
                ->required(),
            DatePicker::make('trans_date')
                ->label('Tanggal')
                ->required()
                ->default(now()),
            Select::make('status')
                ->label('Status')
                ->options([
                    'pending' => 'Pending',
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                ])
                ->default('pending')
                ->required()
                ->disabled(fn (?EmployeeTransaction $record) => $record && $record->status !== 'pending' && ! (Auth::user()?->isAdmin() ?? false))
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
                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('trans_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('outlet.name')
                    ->label('Outlet')
                    ->sortable(),
                TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Nominal Kasbon')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('outlet_id')->label('Outlet')->relationship('outlet', 'name'),
                SelectFilter::make('employee_id')->label('Karyawan')->relationship('employee', 'name'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),
            ])
            ->defaultSort('trans_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKasbons::route('/'),
            'create' => Pages\CreateKasbon::route('/create'),
            'edit' => Pages\EditKasbon::route('/{record}/edit'),
        ];
    }
}
