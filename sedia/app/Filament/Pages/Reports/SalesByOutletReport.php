<?php

namespace App\Filament\Pages\Reports;

use App\Models\Outlet;
use App\Models\SalesTransaction;
use App\Support\OutletContext;
use BackedEnum;
use Illuminate\Support\Collection;

class SalesByOutletReport extends Report
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Penjualan per Outlet';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Laporan Penjualan per Outlet';

    protected string $view = 'filament.pages.reports.sales-by-outlet-report';

    /**
     * @return Collection<int, array{outlet_id: int, outlet_name: string, trx_count: int, total_omzet: float, aov: float}>
     */
    public function getRows(): Collection
    {
        $query = SalesTransaction::query()
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$this->periodStart(), $this->periodEnd()]);

        if ($this->outletId) {
            $query->where('outlet_id', $this->outletId);
        } elseif (! $this->isAdminUser()) {
            $query->where('outlet_id', OutletContext::currentOutletId());
        }

        $rows = $query
            ->selectRaw('outlet_id, COUNT(*) as trx_count, SUM(total_amount) as total_omzet')
            ->groupBy('outlet_id')
            ->orderByDesc('total_omzet')
            ->get();

        $outletNames = Outlet::query()->pluck('name', 'id');

        return $rows->map(function ($row) use ($outletNames) {
            $trxCount = (int) $row->trx_count;
            $totalOmzet = (float) $row->total_omzet;

            return [
                'outlet_id' => $row->outlet_id,
                'outlet_name' => $outletNames->get($row->outlet_id, '—'),
                'trx_count' => $trxCount,
                'total_omzet' => $totalOmzet,
                'aov' => $trxCount > 0 ? $totalOmzet / $trxCount : 0,
            ];
        });
    }

    public function getGrandTotal(Collection $rows): array
    {
        $trxCount = (int) $rows->sum('trx_count');
        $totalOmzet = (float) $rows->sum('total_omzet');

        return [
            'trx_count' => $trxCount,
            'total_omzet' => $totalOmzet,
            'aov' => $trxCount > 0 ? $totalOmzet / $trxCount : 0,
        ];
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function getSummary(): array
    {
        $grandTotal = $this->getGrandTotal($this->getRows());

        return [
            ['label' => 'Total Omzet', 'value' => $this->formatRupiah($grandTotal['total_omzet'])],
            ['label' => 'Jumlah Transaksi', 'value' => number_format($grandTotal['trx_count'])],
            ['label' => 'Rata-rata / Transaksi', 'value' => $this->formatRupiah($grandTotal['aov'])],
        ];
    }
}
