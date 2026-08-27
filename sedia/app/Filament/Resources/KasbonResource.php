<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KasbonResource\Pages;
use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\EmployeeTransaction;
use App\Models\User;
use App\Support\OutletContext;
use App\Support\RolePermission;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
        return RolePermission::can(OutletContext::user(), 'KasbonResource', 'create');
    }

    public static function canEdit(Model $record): bool
    {
        $user = OutletContext::user();

        if (! RolePermission::can($user, 'KasbonResource', 'edit')) {
            return false;
        }

        if ($record->status !== 'pending') {
            return $user?->isAdmin() ?? false;
        }

        return $user?->isAdmin() || $record->outlet_id === $user?->outlet_id;
    }

    public static function canDelete(Model $record): bool
    {
        return RolePermission::can(OutletContext::user(), 'KasbonResource', 'delete');
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
                ->disabled(fn (?EmployeeTransaction $record) => $record && $record->status !== 'pending' && ! (OutletContext::user()?->isAdmin() ?? false))
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
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (EmployeeTransaction $record) => $record->status === 'pending' && (OutletContext::user()?->isAdmin() ?? false))
                    ->requiresConfirmation()
                    ->action(function (EmployeeTransaction $record) {
                        $record->update(['status' => 'approved']);
                        ActivityLog::record('approved', $record, 'Kasbon '.$record->employee->name.' disetujui');
                        Notification::make()->title('Kasbon disetujui')->success()->send();
                        // Notif ke admin lain & kasir terkait
                        $recipients = User::where('role', 'admin')->get();
                        if ($recipients->isNotEmpty()) {
                            Notification::make()->title('Kasbon disetujui')->body($record->employee->name.' — Rp '.number_format($record->amount, 0, ',', '.'))->sendToDatabase($recipients);
                        }
                    }),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (EmployeeTransaction $record) => $record->status === 'pending' && (OutletContext::user()?->isAdmin() ?? false))
                    ->requiresConfirmation()
                    ->action(function (EmployeeTransaction $record) {
                        $record->update(['status' => 'rejected']);
                        ActivityLog::record('rejected', $record, 'Kasbon '.$record->employee->name.' ditolak');
                        Notification::make()->title('Kasbon ditolak')->warning()->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('approveSelected')
                        ->label('Setujui terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn () => OutletContext::user()?->isAdmin() ?? false)
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->where('status', 'pending')->each->update(['status' => 'approved']);
                            Notification::make()->title('Kasbon terpilih disetujui')->success()->send();
                        }),
                    BulkAction::make('rejectSelected')
                        ->label('Tolak terpilih')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn () => OutletContext::user()?->isAdmin() ?? false)
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->where('status', 'pending')->each->update(['status' => 'rejected']);
                            Notification::make()->title('Kasbon terpilih ditolak')->warning()->send();
                        }),
                    DeleteBulkAction::make()->visible(fn () => OutletContext::user()?->isAdmin() ?? false),
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
