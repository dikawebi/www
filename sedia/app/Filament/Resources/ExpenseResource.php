<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use App\Models\User;
use App\Support\OutletContext;
use App\Support\RolePermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
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

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Kas Kecil';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return RolePermission::can(OutletContext::user(), 'ExpenseResource', 'create');
    }

    public static function canEdit(Model $record): bool
    {
        $user = OutletContext::user();

        if (! RolePermission::can($user, 'ExpenseResource', 'edit')) {
            return false;
        }

        return $user?->isAdmin() || $record->outlet_id === $user?->outlet_id;
    }

    public static function canDelete(Model $record): bool
    {
        return RolePermission::can(OutletContext::user(), 'ExpenseResource', 'delete');
    }

    public static function getEloquentQuery(): Builder
    {
        return OutletContext::visibleQuery(parent::getEloquentQuery());
    }

    public static function form(Schema $schema): Schema
    {
        /** @var User|null $user */
        $user = Auth::user();
        $isAdmin = $user?->isAdmin() ?? false;

        return $schema->components([
            Select::make('outlet_id')
                ->label('Outlet')
                ->options(OutletContext::selectableOutletOptions())
                ->searchable()->preload()
                ->default(fn () => OutletContext::defaultOutletId())
                ->required()
                ->disabled(! $isAdmin)->dehydrated(true)
                ->rules([
                    function () {
                        return function (string $attribute, $value, \Closure $fail) {
                            if (filled($value) && ! array_key_exists((int) $value, OutletContext::selectableOutletOptions())) {
                                $fail('Outlet tidak valid.');
                            }
                        };
                    },
                ]),
            Select::make('category')
                ->label('Kategori')
                ->options([
                    'listrik' => 'Listrik',
                    'air' => 'Air',
                    'gas' => 'Gas',
                    'plastik' => 'Plastik/Kemasan',
                    'perawatan' => 'Perawatan',
                    'sewa' => 'Sewa',
                    'bahan_baku' => 'Bahan Baku',
                    'lainnya' => 'Lainnya',
                ])->required()->default('lainnya'),
            TextInput::make('description')->label('Deskripsi')->required()->maxLength(255)->placeholder('Mis: Beli plastik 2 pack'),
            TextInput::make('amount')->label('Nominal')->numeric()->prefix('Rp')->required()->minValue(0),
            DatePicker::make('expense_date')->label('Tanggal')->required()->default(now()),
            Textarea::make('note')->label('Catatan')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('expense_date')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('outlet.name')->label('Outlet')->sortable(),
                TextColumn::make('category')->label('Kategori')->badge()->color(fn ($s) => match ($s) {
                    'listrik' => 'warning', 'air' => 'info', 'gas' => 'danger', 'lainnya' => 'gray', default => 'primary',
                }),
                TextColumn::make('description')->label('Deskripsi')->searchable()->limit(30),
                TextColumn::make('amount')->label('Nominal')->money('IDR')->sortable(),
                TextColumn::make('creator.name')->label('Input')->default('-'),
            ])
            ->filters([
                SelectFilter::make('outlet_id')->label('Outlet')->relationship('outlet', 'name'),
                SelectFilter::make('category')->options([
                    'listrik' => 'Listrik', 'air' => 'Air', 'gas' => 'Gas', 'plastik' => 'Plastik', 'perawatan' => 'Perawatan', 'sewa' => 'Sewa', 'bahan_baku' => 'Bahan Baku', 'lainnya' => 'Lainnya',
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => OutletContext::user()?->isAdmin() ?? false),
                ]),
            ])
            ->defaultSort('expense_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
