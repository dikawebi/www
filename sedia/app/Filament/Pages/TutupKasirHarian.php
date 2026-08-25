<?php

namespace App\Filament\Pages;

use App\Models\Outlet;
use App\Models\SalesTransaction;
use App\Support\OutletContext;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use UnitEnum;

class TutupKasirHarian extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Tutup Kasir Harian';

    protected static ?string $title = 'Tutup Kasir Harian';

    protected string $view = 'filament.pages.tutup-kasir-harian';

    public string $selectedDate;

    public ?int $outletId = null;

    public function mount(): void
    {
        $this->selectedDate = request()->query('date') ?: now()->toDateString();
        $requestedOutletId = request()->query('outlet_id');
        $this->outletId = filled($requestedOutletId) ? (int) $requestedOutletId : null;

        $user = OutletContext::user();
        if ($user && ! $user->isAdmin()) {
            $this->outletId = $user->outlet_id;
        }
    }

    public function outletOptions(): array
    {
        return OutletContext::selectableOutletOptions();
    }

    public function isAdminUser(): bool
    {
        return OutletContext::user()?->isAdmin() ?? false;
    }

    public function formatRupiah(float|int|string|null $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }

    public function currentOutletName(): string
    {
        $outletId = $this->outletId ?? OutletContext::currentOutletId();
        if (! $outletId) {
            return 'Semua Outlet';
        }

        return Outlet::find($outletId)?->name ?? '—';
    }

    /**
     * @return Collection<int, array{cashier_id: int|null, cashier_name: string, trx_count: int, total: float, by_payment: array<string, float>}>
     */
    public function getRows(): Collection
    {
        $query = SalesTransaction::query()
            ->with('cashier')
            ->where('status', 'completed')
            ->whereDate('transaction_date', $this->selectedDate);

        if ($this->outletId) {
            $query->where('outlet_id', $this->outletId);
        } elseif (! $this->isAdminUser()) {
            $query->where('outlet_id', OutletContext::currentOutletId());
        }

        $transactions = $query->get();

        return $transactions->groupBy(fn ($t) => $t->cashier_id ?? 0)->map(function (Collection $group) {
            $first = $group->first();
            $byPayment = $group->groupBy('payment_method')->map(fn (Collection $g) => (float) $g->sum('total_amount'));

            return [
                'cashier_id' => $first->cashier_id,
                'cashier_name' => $first->cashier?->name ?? 'Tanpa Kasir',
                'trx_count' => $group->count(),
                'total' => (float) $group->sum('total_amount'),
                'by_payment' => $byPayment->all(),
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
}
