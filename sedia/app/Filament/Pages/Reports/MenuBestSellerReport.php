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
            ->selectRaw('menu_items.id as menu_item_id, menu_items.name as menu_name, menu_items.category as category, SUM(sales_transaction_items.quantity) as qty_sold, SUM(sales_transaction_items.subtotal) as total_revenue')
            ->groupBy('menu_items.id', 'menu_items.name', 'menu_items.category')
            ->orderByDesc('qty_sold')
            ->get();

        // Retur per menu dalam periode (kurangi qty & revenue) — net
        $returQuery = DB::table('sales_return_items')
            ->join('sales_returns', 'sales_returns.id', '=', 'sales_return_items.sales_return_id')
            ->whereBetween('sales_returns.created_at', [$this->periodStart(), $this->periodEnd()]);
        if ($this->outletId) {
            $returQuery->where('sales_returns.outlet_id', $this->outletId);
        } elseif (! $this->isAdminUser()) {
            $returQuery->where('sales_returns.outlet_id', OutletContext::currentOutletId());
        }
        $returData = $returQuery->selectRaw('sales_return_items.menu_item_id, SUM(sales_return_items.quantity) as ret_qty, SUM(sales_return_items.subtotal) as ret_rev')
            ->groupBy('sales_return_items.menu_item_id')->get()->keyBy('menu_item_id');

        return collect($rows)->map(function ($row) use ($returData) {
            $ret = $returData->get($row->menu_item_id);
            $retQty = (int) ($ret->ret_qty ?? 0);
            $retRev = (float) ($ret->ret_rev ?? 0);

            return [
                'menu_name' => $row->menu_name,
                'category' => $row->category,
                'qty_sold' => max(0, (int) $row->qty_sold - $retQty),
                'total_revenue' => max(0, (float) $row->total_revenue - $retRev),
            ];
        });
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
