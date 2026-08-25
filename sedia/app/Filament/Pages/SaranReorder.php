<?php

namespace App\Filament\Pages;

use App\Models\Ingredient;
use App\Models\Stock;
use App\Support\OutletContext;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class SaranReorder extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-light-bulb';

    protected static string|UnitEnum|null $navigationGroup = 'Persediaan';

    protected static ?string $navigationLabel = 'Saran Reorder';

    protected static ?string $title = 'Saran Reorder Stok';

    protected string $view = 'filament.pages.saran-reorder';

    public ?int $outletId = null;

    public function mount(): void
    {
        $requested = request()->query('outlet_id');
        $this->outletId = filled($requested) ? (int) $requested : null;
        $user = OutletContext::user();
        if ($user && ! $user->isAdmin()) {
            $this->outletId = $user->outlet_id;
        }
        if (! $this->outletId) {
            $this->outletId = OutletContext::currentOutletId();
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

    public function formatQty(float $value, string $unit): string
    {
        return number_format($value, 2).' '.$unit;
    }

    /**
     * @return Collection<int, array{ingredient_name: string, unit: string, current: float, min_stock: float, avg_daily: float, days_remaining: float|null, suggested: float}>
     */
    public function getRows(): Collection
    {
        $outletId = $this->outletId;
        if (! $outletId) {
            return collect();
        }

        $ingredients = Ingredient::where('is_active', true)->get(['id', 'name', 'unit', 'min_stock']);
        $stocks = Stock::where('outlet_id', $outletId)->pluck('quantity', 'ingredient_id');

        $thirtyDaysAgo = now()->subDays(30);
        $outMovements = DB::table('stock_movements')
            ->where('outlet_id', $outletId)
            ->whereIn('type', ['sale_deduction', 'expired', 'reject', 'transfer_out'])
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('ingredient_id, SUM(ABS(quantity)) as total_out')
            ->groupBy('ingredient_id')
            ->pluck('total_out', 'ingredient_id');

        return $ingredients->map(function (Ingredient $ing) use ($stocks, $outMovements) {
            $current = (float) ($stocks->get($ing->id) ?? 0);
            $min = (float) $ing->min_stock;
            $totalOut30 = (float) ($outMovements->get($ing->id) ?? 0);
            $avg = $totalOut30 / 30;
            $days = $avg > 0.001 ? $current / $avg : null;
            $suggested = 0;
            if ($min > 0 || $avg > 0) {
                $target = $min + ($avg * 7);
                $suggested = max(0, ceil($target - $current));
            }

            return [
                'ingredient_name' => $ing->name,
                'unit' => $ing->unit,
                'current' => $current,
                'min_stock' => $min,
                'avg_daily' => $avg,
                'days_remaining' => $days,
                'suggested' => $suggested,
            ];
        })->filter(fn ($row) => $row['suggested'] > 0 || $row['current'] < $row['min_stock'])->sortByDesc('suggested')->values();
    }
}
