<?php

namespace App\Filament\Pages\Pos;

use App\Models\SalesTransaction;
use App\Support\OutletContext;
use App\Support\RolePermission;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use UnitEnum;

class TransactionHistory extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|UnitEnum|null $navigationGroup = 'Kasir';

    protected static ?string $navigationLabel = 'Riwayat Transaksi';

    protected static ?string $title = 'Riwayat Transaksi';

    protected string $view = 'filament.pages.pos.transaction-history';

    public static function canAccess(): bool
    {
        return RolePermission::can(OutletContext::user(), 'SalesTransactionResource', 'view');
    }

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?int $outletId = null;

    public function mount(): void
    {
        $this->startDate = now()->subDays(7)->toDateString();
        $this->endDate = now()->toDateString();
        $user = OutletContext::user();
        if ($user && ! $user->isAdmin()) {
            $this->outletId = $user->outlet_id;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('startDate')->label('Dari')->required(),
            DatePicker::make('endDate')->label('Sampai')->required(),
            Select::make('outletId')
                ->label('Outlet')
                ->options(fn () => OutletContext::selectableOutletOptions())
                ->searchable()
                ->preload()
                ->placeholder('Semua outlet')
                ->nullable()
                ->disabled(! (OutletContext::user()?->isAdmin() ?? false)),
        ]);
    }

    public function getTransactions(): Collection
    {
        $data = $this->form->getState();
        $query = SalesTransaction::query()
            ->with('outlet', 'cashier', 'items.menuItem')
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$data['startDate'] ?? $this->startDate, ($data['endDate'] ?? $this->endDate).' 23:59:59']);

        if ($data['outletId'] ?? $this->outletId) {
            $query->where('outlet_id', $data['outletId'] ?? $this->outletId);
        } elseif (! (OutletContext::user()?->isAdmin() ?? false)) {
            $query->where('outlet_id', OutletContext::currentOutletId());
        }

        return $query->latest('transaction_date')->limit(100)->get();
    }

    public function formatRupiah(float|int|null $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }

    public function isAdminUser(): bool
    {
        return OutletContext::user()?->isAdmin() ?? false;
    }

    public function outletOptions(): array
    {
        return OutletContext::selectableOutletOptions();
    }
}
