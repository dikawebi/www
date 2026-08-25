<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesTransactionResource\Pages;
use App\Models\Employee;
use App\Models\MenuItem;
use App\Models\SalesTransaction;
use App\Models\User;
use App\Support\OutletContext;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use UnitEnum;

class SalesTransactionResource extends Resource
{
    protected static ?string $model = SalesTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string|UnitEnum|null $navigationGroup = 'Kasir';

    protected static ?string $navigationLabel = 'Transaksi Penjualan';

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(Model $record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->isAdmin() || $record->outlet_id === $user?->outlet_id;
    }

    public static function canDelete(Model $record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->isAdmin() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        /** @var User|null $user */
        $user = Auth::user();

        if ($user && ! $user->isAdmin() && ! $user->outlet_id) {
            return $query->whereRaw('1 = 0');
        }

        $outletId = $user?->accessibleOutletId();

        return $outletId ? $query->where('outlet_id', $outletId) : $query;
    }

    public static function form(Schema $schema): Schema
    {
        $isAdmin = Auth::user()?->isAdmin() ?? false;

        return $schema->components([
            Select::make('outlet_id')
                ->label('Outlet')
                ->options(fn () => OutletContext::selectableOutletOptions())
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
                ->afterStateUpdated(fn ($set) => $set('cashier_id', null)),
            Select::make('cashier_id')
                ->label('Kasir')
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
                ->helperText('Daftar kasir mengikuti outlet yang dipilih di atas.'),
            TextInput::make('invoice_number')
                ->label('No. Invoice')
                ->default(fn () => 'INV-'.now()->format('Ymd').'-'.strtoupper(Str::random(5)))
                ->required()
                ->maxLength(50),
            DateTimePicker::make('transaction_date')
                ->label('Waktu Transaksi')
                ->required()
                ->default(now()),
            Select::make('payment_method')
                ->label('Metode Pembayaran')
                ->options([
                    'cash' => 'Tunai',
                    'transfer' => 'Transfer Bank',
                    'qris' => 'QRIS',
                    'debit' => 'Kartu Debit',
                ])
                ->default('cash')
                ->required(),
            Select::make('status')
                ->label('Status')
                ->options([
                    'completed' => 'Selesai',
                    'void' => 'Batal',
                ])
                ->default('completed')
                ->required(),

            // PENTING: ini cuma preview di layar. Nilai yang BENAR-BENAR tersimpan
            // ke DB dihitung ulang di server oleh SalesTransaction::recalculateTotalAmount(),
            // dipanggil dari SalesTransactionItemObserver setiap kali item disimpan/dihapus
            // (lihat app/Observers/SalesTransactionItemObserver.php). Sengaja PAKAI Placeholder,
            // BUKAN TextInput + afterStateUpdated — karena Placeholder->content() selalu
            // dievaluasi ulang tiap kali Livewire re-render (termasuk pas hapus baris repeater),
            // sedangkan afterStateUpdated/deleteAction di Repeater v4 terbukti tidak reliable
            // untuk kasus delete row (lihat filamentphp/filament#17225 dan #18008).
            Placeholder::make('total_amount_preview')
                ->label('Total (preview)')
                ->content(function (Get $get): string {
                    $items = $get('items') ?? [];
                    $sum = collect($items)->sum(fn ($item) => (float) ($item['subtotal'] ?? 0));

                    return 'Rp '.number_format($sum, 0, ',', '.');
                }),

            Repeater::make('items')
                ->label('Item Pesanan')
                ->relationship('items')
                ->live()
                ->schema([
                    Select::make('menu_item_id')
                        ->label('Menu')
                        ->options(fn () => MenuItem::where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                            $menu = $state ? MenuItem::find($state) : null;
                            $price = $menu ? (float) $menu->price : 0;

                            $set('price', $price);
                            $set('subtotal', $price * (int) $get('quantity'));
                        }),
                    TextInput::make('quantity')
                        ->label('Qty')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set): void {
                            $set('subtotal', (float) $get('price') * (int) $get('quantity'));
                        }),
                    TextInput::make('price')
                        ->label('Harga')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set): void {
                            $set('subtotal', (float) $get('price') * (int) $get('quantity'));
                        }),
                    TextInput::make('subtotal')
                        ->label('Subtotal')
                        ->numeric()
                        ->prefix('Rp')
                        ->disabled()
                        ->dehydrated(),
                ])
                ->columns(4)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable(),
                TextColumn::make('outlet.name')
                    ->label('Outlet'),
                TextColumn::make('cashier.name')
                    ->label('Kasir'),
                TextColumn::make('items_count')
                    ->label('Item')
                    ->counts('items'),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Bayar')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state === 'completed' ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('outlet_id')
                    ->label('Outlet')
                    ->relationship('outlet', 'name'),
                SelectFilter::make('status')
                    ->options([
                        'completed' => 'Selesai',
                        'void' => 'Batal',
                    ]),
                SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                        'debit' => 'Debit',
                    ]),
            ])
            ->recordActions([ViewAction::make()])
            ->defaultSort('transaction_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesTransactions::route('/'),
            'create' => Pages\CreateSalesTransaction::route('/create'),
            'view' => Pages\ViewSalesTransaction::route('/{record}'),
        ];
    }
}
