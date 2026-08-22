<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockTransferResource\Pages;
use App\Filament\Resources\StockTransferResource\RelationManagers\ItemsRelationManager;
use App\Models\StockTransfer;
use App\Models\User;
use App\Services\StockService;
use App\Support\OutletContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
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
use UnitEnum;

class StockTransferResource extends Resource
{
    protected static ?string $model = StockTransfer::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static string|UnitEnum|null $navigationGroup = 'Persediaan';
    protected static ?string $navigationLabel = 'Transfer Stok';
    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool { return (bool) Auth::user(); }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        if ($user?->isAdmin()) {
            return true;
        }

        return $record->from_outlet_id === $user?->outlet_id && $record->status === 'draft';
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();
        return $user?->isAdmin() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user?->isAdmin()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('from_outlet_id', $user?->outlet_id)
                ->orWhere('to_outlet_id', $user?->outlet_id);
        });
    }

    public static function form(Schema $schema): Schema
    {
        /** @var User $user */
        $user = Auth::user();
        $isAdmin = $user?->isAdmin() ?? false;
        $currentOutletId = OutletContext::defaultOutletId();

        return $schema->components([
            Select::make('source')
                ->label('Sumber')
                ->options(['transfer' => 'Transfer antar outlet', 'purchase' => 'Belanja dari stockist'])
                ->default('transfer')
                ->required()
                ->reactive(),
            Select::make('from_outlet_id')
                ->label('Dari Outlet')
                ->options(OutletContext::selectableOutletOptions())
                ->searchable()
                ->preload()
                ->default(fn () => $currentOutletId)
                ->disabled(! $isAdmin)
                ->required()
                ->dehydrated(true),
            Select::make('to_outlet_id')
                ->label('Ke Outlet')
                ->options(OutletContext::selectableOutletOptions())
                ->searchable()
                ->preload()
                ->default(fn () => $currentOutletId)
                ->required(),
            DateTimePicker::make('transferred_at')
                ->label('Waktu Pengiriman')
                ->required()
                ->default(now()),
            Select::make('status')
                ->label('Status')
                ->options([
                    'draft' => 'Draft (Belum Dikirim)',
                    'sent' => 'Dikirim (Dalam Perjalanan)',
                    'received' => 'Diterima (Selesai)',
                    'cancelled' => 'Batal',
                ])
                ->default('draft')
                ->disabled()
                ->dehydrated(),
            Textarea::make('note')->label('Catatan')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transferred_at')->label('Waktu Kirim')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('source')->label('Sumber')->badge()->color(fn ($state) => $state === 'purchase' ? 'info' : 'warning'),
                TextColumn::make('fromOutlet.name')->label('Dari Outlet')->default('(Stockist)'),
                TextColumn::make('toOutlet.name')->label('Ke Outlet'),
                TextColumn::make('items_count')->label('Jml Item')->counts('items'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'received' => 'success',
                        'sent' => 'warning',
                        'draft' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'draft' => 'Draft',
                        'sent' => 'Dikirim',
                        'received' => 'Diterima',
                        'cancelled' => 'Batal',
                        default => $state,
                    }),
                TextColumn::make('receiver.name')->label('Penerima')->default('-'),
            ])
            ->filters([
                SelectFilter::make('to_outlet_id')->label('Ke Outlet')->relationship('toOutlet', 'name'),
                SelectFilter::make('source')->options(['transfer' => 'Transfer', 'purchase' => 'Pembelian']),
                SelectFilter::make('status')->options([
                    'draft' => 'Draft', 'sent' => 'Dikirim', 'received' => 'Diterima', 'cancelled' => 'Batal',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('ship')
                    ->label('Kirim')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pengiriman')
                    ->modalDescription('Stok di outlet asal akan dipotong setelah barang dikirim.')
                    ->visible(function (StockTransfer $record) {
                        if ($record->status !== 'draft') return false;
                        if ($record->source === 'purchase') return false;
                        $user = Auth::user();
                        return $user?->isAdmin() || $record->from_outlet_id === $user?->outlet_id;
                    })
                    ->action(function (StockTransfer $record) {
                        if ($record->items()->count() === 0) {
                            Notification::make()->title('Item kosong')->body('Tambahkan item transfer dulu.')->danger()->send();
                            return;
                        }
                        try {
                            app(StockService::class)->shipTransfer($record, Auth::id());
                            Notification::make()->title('Barang berhasil dikirim')->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title('Stok tidak cukup')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('receive')
                    ->label('Terima')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Penerimaan Barang')
                    ->modalDescription('Stok akan masuk dan ditambahkan ke gudang outlet tujuan.')
                    ->visible(function (StockTransfer $record) {
                        $user = Auth::user();
                        $isAuthorized = $user?->isAdmin() || $record->to_outlet_id === $user?->outlet_id;
                        if (! $isAuthorized) return false;
                        if ($record->source === 'purchase') return in_array($record->status, ['draft', 'pending', 'sent']);
                        return $record->status === 'sent';
                    })
                    ->action(function (StockTransfer $record) {
                        if ($record->items()->count() === 0) {
                            Notification::make()->title('Item kosong')->body('Tambahkan item transfer dulu.')->danger()->send();
                            return;
                        }
                        try {
                            app(StockService::class)->receiveTransfer($record, Auth::id());
                            Notification::make()->title('Barang berhasil diterima & masuk gudang')->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title('Gagal memproses')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->defaultSort('transferred_at', 'desc');
    }

    public static function getRelations(): array { return [ItemsRelationManager::class]; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockTransfers::route('/'),
            'create' => Pages\CreateStockTransfer::route('/create'),
            'edit' => Pages\EditStockTransfer::route('/{record}/edit'),
        ];
    }
}