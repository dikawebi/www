<?php

namespace App\Filament\Pages\Reports;

use App\Models\Expense;
use App\Support\OutletContext;
use BackedEnum;
use Illuminate\Support\Collection;

class ExpenseReport extends Report
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationLabel = 'Pengeluaran';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Laporan Pengeluaran (Kas Kecil)';

    protected string $view = 'filament.pages.reports.expense-report';

    /**
     * @return Collection<int, array{outlet_id: int, outlet_name: string, category: string, trx_count: int, total_amount: float}>
     */
    public function getRows(): Collection
    {
        $query = Expense::query()
            ->with('outlet')
            ->whereBetween('expense_date', [$this->startDate, $this->endDate]);

        if ($this->outletId) {
            $query->where('outlet_id', $this->outletId);
        } elseif (! $this->isAdminUser()) {
            $query->where('outlet_id', OutletContext::currentOutletId());
        }

        $rows = $query->get()->groupBy(fn ($e) => $e->outlet_id.'|'.$e->category)->map(function (Collection $group) {
            $first = $group->first();

            return [
                'outlet_id' => $first->outlet_id,
                'outlet_name' => $first->outlet?->name ?? '—',
                'category' => $first->category,
                'trx_count' => $group->count(),
                'total_amount' => (float) $group->sum('amount'),
            ];
        })->sortByDesc('total_amount')->values();

        return $rows;
    }

    /**
     * @return Collection<int, array{outlet_name: string, trx_count: int, total_amount: float}>
     */
    public function getPerOutletRows(): Collection
    {
        return $this->getRows()->groupBy('outlet_id')->map(function (Collection $group) {
            $first = $group->first();

            return [
                'outlet_name' => $first['outlet_name'],
                'trx_count' => $group->sum('trx_count'),
                'total_amount' => $group->sum('total_amount'),
            ];
        })->sortByDesc('total_amount')->values();
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function getSummary(): array
    {
        $rows = $this->getRows();
        $total = (float) $rows->sum('total_amount');
        $trx = (int) $rows->sum('trx_count');
        $topCategory = $rows->groupBy('category')->map(fn ($g) => $g->sum('total_amount'))->sortDesc()->keys()->first() ?? '-';

        return [
            ['label' => 'Total Pengeluaran', 'value' => $this->formatRupiah($total)],
            ['label' => 'Jumlah Transaksi', 'value' => number_format($trx)],
            ['label' => 'Kategori Terbesar', 'value' => ucfirst(str_replace('_', ' ', $topCategory))],
        ];
    }
}
