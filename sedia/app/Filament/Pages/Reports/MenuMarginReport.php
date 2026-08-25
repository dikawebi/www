<?php

namespace App\Filament\Pages\Reports;

use App\Models\MenuItem;
use App\Support\OutletContext;
use BackedEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MenuMarginReport extends Report
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Laba Kotor per Menu';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Laporan Laba Kotor per Menu';

    protected string $view = 'filament.pages.reports.menu-margin-report';

    /**
     * @return Collection<int, array{menu_name: string, hpp_per_unit: float, price: float, margin_per_unit: float, qty_sold: int, revenue: float, total_hpp: float, gross_margin: float, margin_pct: float}>
     */
    public function getRows(): Collection
    {
        // 1. HPP per unit tiap menu, dari resep x harga beli bahan baku.
        $menuItems = MenuItem::query()
            ->with('recipes.ingredient')
            ->where('is_active', true)
            ->get();

        $hppByMenu = $menuItems->mapWithKeys(function (MenuItem $menuItem) {
            $hpp = $menuItem->recipes->sum(
                fn ($recipe) => (float) $recipe->qty_per_unit * (float) ($recipe->ingredient?->cost_per_unit ?? 0)
            );

            return [$menuItem->id => $hpp];
        });

        // 2. Qty terjual & omzet tiap menu dalam periode.
        $salesQuery = DB::table('sales_transaction_items')
            ->join('sales_transactions', 'sales_transactions.id', '=', 'sales_transaction_items.sales_transaction_id')
            ->where('sales_transactions.status', 'completed')
            ->whereBetween('sales_transactions.transaction_date', [$this->periodStart(), $this->periodEnd()]);

        if ($this->outletId) {
            $salesQuery->where('sales_transactions.outlet_id', $this->outletId);
        } elseif (! $this->isAdminUser()) {
            $salesQuery->where('sales_transactions.outlet_id', OutletContext::currentOutletId());
        }

        $sales = $salesQuery
            ->selectRaw('sales_transaction_items.menu_item_id, SUM(sales_transaction_items.quantity) as qty_sold, SUM(sales_transaction_items.subtotal) as revenue')
            ->groupBy('sales_transaction_items.menu_item_id')
            ->get()
            ->keyBy('menu_item_id');

        // 3. Gabungkan: hanya tampilkan menu yang benar-benar terjual di periode ini.
        return $sales->map(function ($saleRow) use ($menuItems, $hppByMenu) {
            $menuItem = $menuItems->firstWhere('id', $saleRow->menu_item_id);
            $hppPerUnit = (float) ($hppByMenu->get($saleRow->menu_item_id) ?? 0);
            $qtySold = (int) $saleRow->qty_sold;
            $revenue = (float) $saleRow->revenue;
            $totalHpp = $hppPerUnit * $qtySold;
            $grossMargin = $revenue - $totalHpp;

            return [
                'menu_name' => $menuItem?->name ?? '—',
                'hpp_per_unit' => $hppPerUnit,
                'price' => (float) ($menuItem?->price ?? 0),
                'margin_per_unit' => (float) ($menuItem?->price ?? 0) - $hppPerUnit,
                'qty_sold' => $qtySold,
                'revenue' => $revenue,
                'total_hpp' => $totalHpp,
                'gross_margin' => $grossMargin,
                'margin_pct' => $revenue > 0 ? ($grossMargin / $revenue) * 100 : 0,
            ];
        })
            ->sortByDesc('gross_margin')
            ->values();
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function getSummary(): array
    {
        $rows = $this->getRows();

        $revenue = (float) $rows->sum('revenue');
        $totalHpp = (float) $rows->sum('total_hpp');
        $grossMargin = (float) $rows->sum('gross_margin');
        $marginPct = $revenue > 0 ? ($grossMargin / $revenue) * 100 : 0;

        return [
            ['label' => 'Total Omzet', 'value' => $this->formatRupiah($revenue)],
            ['label' => 'Total HPP', 'value' => $this->formatRupiah($totalHpp)],
            ['label' => 'Laba Kotor', 'value' => $this->formatRupiah($grossMargin)],
            ['label' => 'Margin Rata-rata', 'value' => number_format($marginPct, 1).'%'],
        ];
    }
}
