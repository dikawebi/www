<?php

namespace App\Filament\Pages\Pos;

use App\Models\Outlet;
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

class ClosingReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Kasir';

    protected static ?string $navigationLabel = 'Tutup Kasir';

    protected static ?string $title = 'Tutup Kasir Harian';

    protected string $view = 'filament.pages.pos.closing-report';

    public static function canAccess(): bool
    {
        return RolePermission::can(OutletContext::user(), 'TutupKasirHarian', 'view');
    }

    public ?string $selectedDate = null;

    public ?int $outletId = null;

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();
        $user = OutletContext::user();
        if ($user && ! $user->isAdmin()) {
            $this->outletId = $user->outlet_id;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('selectedDate')
                ->label('Tanggal')
                ->required()
                ->live(),
            Select::make('outletId')
                ->label('Outlet')
                ->options(fn () => OutletContext::selectableOutletOptions())
                ->searchable()
                ->preload()
                ->placeholder('Semua outlet')
                ->nullable()
                ->disabled(! (OutletContext::user()?->isAdmin() ?? false))
                ->live(),
        ]);
    }

    public function getRows(): Collection
    {
        $data = $this->form->getState();
        $date = $data['selectedDate'] ?? $this->selectedDate;
        $outletId = $data['outletId'] ?? $this->outletId;

        $query = SalesTransaction::query()
            ->with('cashier')
            ->where('status', 'completed')
            ->whereDate('transaction_date', $date);

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        } elseif (! (OutletContext::user()?->isAdmin() ?? false)) {
            $query->where('outlet_id', OutletContext::currentOutletId());
        }

        $transactions = $query->get();

        return $transactions->groupBy(fn ($t) => $t->cashier_id ?? 0)->map(function (Collection $group) {
            $first = $group->first();
            $byPayment = [];
            foreach ($group as $trx) {
                foreach ($trx->getPaymentsArray() as $pay) {
                    $method = $pay['method'] ?? 'cash';
                    $amount = (float) ($pay['amount'] ?? 0);
                    if ($method === 'cash' && $trx->change_amount > 0) {
                        $amount = max(0, $amount - (float) $trx->change_amount);
                    }
                    $byPayment[$method] = ($byPayment[$method] ?? 0) + $amount;
                }
            }

            return [
                'cashier_name' => $first->cashier?->name ?? 'Tanpa Kasir',
                'trx_count' => $group->count(),
                'total' => (float) $group->sum('total_amount'),
                'by_payment' => $byPayment,
            ];
        })->sortByDesc('total')->values();
    }

    public function getGrandTotal(Collection $rows): array
    {
        $byPayment = [];
        foreach ($rows as $row) {
            foreach ($row['by_payment'] as $method => $amount) {
                $byPayment[$method] = ($byPayment[$method] ?? 0) + $amount;
            }
        }

        return [
            'trx_count' => (int) $rows->sum('trx_count'),
            'total' => (float) $rows->sum('total'),
            'by_payment' => $byPayment,
        ];
    }

    public function isAdminUser(): bool
    {
        return OutletContext::user()?->isAdmin() ?? false;
    }

    public function outletOptions(): array
    {
        return OutletContext::selectableOutletOptions();
    }

    public function currentOutletName(): string
    {
        $outletId = $this->outletId ?? OutletContext::currentOutletId();
        if (! $outletId) {
            return 'Semua Outlet';
        }

        return Outlet::find($outletId)?->name ?? '—';
    }

    public function formatRupiah(float|int|string|null $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
