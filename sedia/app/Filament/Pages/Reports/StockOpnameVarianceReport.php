<?php

namespace App\Filament\Pages\Reports;

use App\Models\Ingredient;
use App\Support\OutletContext;
use BackedEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockOpnameVarianceReport extends Report
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationLabel = 'Selisih Stock Opname';
    protected static ?int $navigationSort = 4;
    protected static ?string $title = 'Laporan Selisih Stock Opname';

    protected string $view = 'filament.pages.reports.stock-opname-variance-report';

    /**
     * @return Collection<int, array{ingredient_name: string, unit: string, opname_count: int, net_difference: float, abs_difference: float}>
     */
    public function getRows(): Collection
    {
        $query = DB::table('stock_opname_items')
            ->join('stock_opnames', 'stock_opnames.id', '=', 'stock_opname_items.stock_opname_id')
            ->where('stock_opnames.status', 'applied')
            ->whereBetween('stock_opnames.opname_date', [$this->startDate, $this->endDate]);

        if ($this->outletId) {
            $query->where('stock_opnames.outlet_id', $this->outletId);
        } elseif (! $this->isAdminUser()) {
            $query->where('stock_opnames.outlet_id', OutletContext::currentOutletId());
        }

        $rows = $query
            ->selectRaw('stock_opname_items.ingredient_id, COUNT(*) as opname_count, SUM(stock_opname_items.difference) as net_difference, SUM(ABS(stock_opname_items.difference)) as abs_difference')
            ->groupBy('stock_opname_items.ingredient_id')
            ->orderByDesc('abs_difference')
            ->get();

        $ingredients = Ingredient::query()->get(['id', 'name', 'unit'])->keyBy('id');

        return collect($rows)->map(function ($row) use ($ingredients) {
            $ingredient = $ingredients->get($row->ingredient_id);

            return [
                'ingredient_name' => $ingredient?->name ?? '—',
                'unit' => $ingredient?->unit ?? '',
                'opname_count' => (int) $row->opname_count,
                'net_difference' => (float) $row->net_difference,
                'abs_difference' => (float) $row->abs_difference,
            ];
        });
    }
}
