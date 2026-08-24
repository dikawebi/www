<?php

namespace App\Filament\Pages\Reports;

use App\Models\Ingredient;
use App\Support\OutletContext;
use BackedEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IngredientConsumptionReport extends Report
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box-arrow-down';
    protected static ?string $navigationLabel = 'Pemakaian Bahan Baku';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Laporan Pemakaian Bahan Baku';

    protected string $view = 'filament.pages.reports.ingredient-consumption-report';

    /**
     * @return Collection<int, array{ingredient_name: string, unit: string, sale_qty: float, waste_qty: float, transfer_out_qty: float, total_out_qty: float}>
     */
    public function getRows(): Collection
    {
        $query = DB::table('stock_movements')
            ->whereBetween('stock_movements.created_at', [$this->periodStart(), $this->periodEnd()])
            ->whereIn('stock_movements.type', ['sale_deduction', 'expired', 'reject', 'transfer_out']);

        if ($this->outletId) {
            $query->where('stock_movements.outlet_id', $this->outletId);
        } elseif (! $this->isAdminUser()) {
            $query->where('stock_movements.outlet_id', OutletContext::currentOutletId());
        }

        $movements = $query
            ->selectRaw('stock_movements.ingredient_id, stock_movements.type, SUM(ABS(stock_movements.quantity)) as total_qty')
            ->groupBy('stock_movements.ingredient_id', 'stock_movements.type')
            ->get();

        $ingredients = Ingredient::query()->get(['id', 'name', 'unit'])->keyBy('id');

        $grouped = collect($movements)->groupBy('ingredient_id');

        return $grouped->map(function (Collection $rows, $ingredientId) use ($ingredients) {
            $ingredient = $ingredients->get($ingredientId);

            $byType = collect($rows)->pluck('total_qty', 'type');

            $saleQty = (float) ($byType->get('sale_deduction') ?? 0);
            $wasteQty = (float) ($byType->get('expired') ?? 0) + (float) ($byType->get('reject') ?? 0);
            $transferOutQty = (float) ($byType->get('transfer_out') ?? 0);

            return [
                'ingredient_name' => $ingredient?->name ?? '—',
                'unit' => $ingredient?->unit ?? '',
                'sale_qty' => $saleQty,
                'waste_qty' => $wasteQty,
                'transfer_out_qty' => $transferOutQty,
                'total_out_qty' => $saleQty + $wasteQty + $transferOutQty,
            ];
        })
            ->sortByDesc('total_out_qty')
            ->values();
    }
}
