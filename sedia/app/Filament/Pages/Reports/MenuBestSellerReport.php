<?php

namespace App\Filament\Pages\Reports;

use App\Support\OutletContext;
use BackedEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MenuBestSellerReport extends Report
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-fire';

    protected static ?string $navigationLabel = 'Menu Terlaris';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Laporan Menu Terlaris';

    protected string $view = 'filament.pages.reports.menu-best-seller-report';

    /**
     * @return Collection<int, array{menu_name: string, category: ?string, qty_sold: int, total_revenue: float}>
     */
    public function getRows(): Collection
    {
        $query = DB::table('sales_transaction_items')
            ->join('sales_transactions', 'sales_transactions.id', '=', 'sales_transaction_items.sales_transaction_id')
            ->join('menu_items', 'menu_items.id', '=', 'sales_transaction_items.menu_item_id')
            ->where('sales_transactions.status', 'completed')
            ->whereBetween('sales_transactions.transaction_date', [$this->periodStart(), $this->periodEnd()]);

        if ($this->outletId) {
            $query->where('sales_transactions.outlet_id', $this->outletId);
        } elseif (! $this->isAdminUser()) {
            $query->where('sales_transactions.outlet_id', OutletContext::currentOutletId());
        }

        $rows = $query
            ->selectRaw('menu_items.name as menu_name, menu_items.category as category, SUM(sales_transaction_items.quantity) as qty_sold, SUM(sales_transaction_items.subtotal) as total_revenue')
            ->groupBy('menu_items.id', 'menu_items.name', 'menu_items.category')
            ->orderByDesc('qty_sold')
            ->get();

        return collect($rows)->map(fn ($row) => [
            'menu_name' => $row->menu_name,
            'category' => $row->category,
            'qty_sold' => (int) $row->qty_sold,
            'total_revenue' => (float) $row->total_revenue,
        ]);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function getSummary(): array
    {
        $rows = $this->getRows();

        return [
            ['label' => 'Total Qty Terjual', 'value' => number_format((int) $rows->sum('qty_sold'))],
            ['label' => 'Total Omzet', 'value' => $this->formatRupiah((float) $rows->sum('total_revenue'))],
            ['label' => 'Jumlah Menu Terjual', 'value' => number_format($rows->count())],
        ];
    }
}
