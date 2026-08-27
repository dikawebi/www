<?php

namespace App\Filament\Resources;

use App\Enums\StockMovementType;
use App\Filament\Resources\StockOpnameResource\Pages;
use App\Filament\Resources\StockOpnameResource\RelationManagers\ItemsRelationManager;
use App\Models\StockOpname;
use App\Models\User;
use App\Services\StockService;
use App\Support\OutletContext;
use App\Support\RolePermission;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class StockOpnameResource extends Resource
{
    protected static ?string $model = StockOpname::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|UnitEnum|null $navigationGroup = 'Persediaan';

    protected static ?string $navigationLabel = 'Stock Opname';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return RolePermission::can(OutletContext::user(), 'StockOpnameResource', 'create');
    }

    public static function canEdit(Model $record): bool
    {
        $user = OutletContext::user();

        if (! RolePermission::can($user, 'StockOpnameResource', 'edit')) {
            return false;
        }

        $isSameOutlet = $record->outlet_id === $user?->outlet_id;

        return ($user?->isAdmin() || $isSameOutlet) && $record->status === 'draft';
    }

    public static function canView(Model $record): bool
    {
        $user = OutletContext::user();

        if (! RolePermission::can($user, 'StockOpnameResource', 'view')) {
            return false;
        }

        return $user?->isAdmin() || $record->outlet_id === $user?->outlet_id;
    }

    public static function canDelete(Model $record): bool
    {
        return RolePermission::can(OutletContext::user(), 'StockOpnameResource', 'delete');
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
                        };
                    },
                ]),
            DatePicker::make('opname_date')->label('Tanggal Opname')->required()->default(now()),
            Select::make('performed_by')
                ->label('Petugas')
                ->relationship('performer', 'name')
                ->searchable()
                ->preload()
                ->default(fn () => Auth::id()),
            Select::make('status')
                ->label('Status')
                ->options(['draft' => 'Draft', 'applied' => 'Diterapkan'])
                ->default('draft')
                ->disabled()
                ->dehydrated(true)
                ->required(),
            Textarea::make('note')->label('Catatan')->columnSpanFull(),
        ])
            ->disabled(fn (?StockOpname $record) => $record?->status === 'applied');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('opname_date')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('outlet.name')->label('Outlet')->sortable(),
                TextColumn::make('performer.name')->label('Petugas'),
                TextColumn::make('items_count')->label('Jml Item')->counts('items'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'draft' => 'warning',
                        'applied' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('outlet_id')->label('Outlet')->relationship('outlet', 'name'),
                SelectFilter::make('status')->options(['draft' => 'Draft', 'applied' => 'Diterapkan']),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('apply')
                    ->label('Terapkan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (StockOpname $record) => $record->status === 'draft')
                    ->action(function (StockOpname $record) {
                        if ($record->items()->count() === 0) {
                            Notification::make()->title('Opname kosong')->body('Tambahkan item dulu.')->danger()->send();

                            return;
                        }

                        try {
                            DB::transaction(function () use ($record) {
                                $fresh = StockOpname::whereKey($record->id)->lockForUpdate()->first();
                                if (! $fresh || $fresh->status !== 'draft') {
                                    throw new \RuntimeException('Opname sudah diproses.');
                                }
                                $service = app(StockService::class);
                                foreach ($fresh->items()->with('ingredient')->get() as $item) {
                                    $diff = (float) $item->actual_qty - (float) $item->system_qty;
                                    if (abs($diff) < 0.0001) {
                                        continue;
                                    }
                                    $service->recordMovement(
                                        outlet: $fresh->outlet,
                                        ingredient: $item->ingredient,
                                        type: StockMovementType::OpnameAdjustment,
                                        quantity: $diff,
                                        reference: $fresh,
                                        createdBy: Auth::id(),
                                        note: "Opname #{$fresh->id}: koreksi {$item->ingredient->name}",
                                    );
                                }
                                $fresh->update(['status' => 'applied']);
                            });
                            Notification::make()->title('Opname diterapkan')->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => OutletContext::user()?->isAdmin() ?? false),
                ]),
            ])
            ->defaultSort('opname_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [ItemsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockOpnames::route('/'),
            'create' => Pages\CreateStockOpname::route('/create'),
            'edit' => Pages\EditStockOpname::route('/{record}/edit'),
        ];
    }
}
