<?php

namespace App\Services;

use App\Support\OutletContext;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\Outlet;
use App\Models\Payroll;
use App\Models\SalesReturn;
use App\Models\SalesTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    private const DEFINITIONS = [
        'sales-by-outlet' => ['key' => 'SalesByOutletReport', 'title' => 'Penjualan per Outlet'],
        'menu-best-seller' => ['key' => 'MenuBestSellerReport', 'title' => 'Menu Terlaris'],
        'ingredient-consumption' => ['key' => 'IngredientConsumptionReport', 'title' => 'Pemakaian Bahan Baku'],
        'stock-opname-variance' => ['key' => 'StockOpnameVarianceReport', 'title' => 'Selisih Stock Opname'],
        'payroll-kasbon' => ['key' => 'PayrollKasbonReport', 'title' => 'Gaji dan Kasbon'],
        'menu-margin' => ['key' => 'MenuMarginReport', 'title' => 'Laba Kotor per Menu'],
        'expense' => ['key' => 'ExpenseReport', 'title' => 'Pengeluaran'],
    ];

    public function definition(string $slug): array
    {
        abort_unless(isset(self::DEFINITIONS[$slug]), 404);
        return self::DEFINITIONS[$slug];
    }

    public function definitions(): array
    {
        return self::DEFINITIONS;
    }

    public function generate(string $slug, string $startDate, string $endDate, ?int $outletId): array
    {
        if ($slug === 'sales-by-outlet') return $this->salesByOutlet($startDate, $endDate, $outletId);
        if ($slug === 'expense') return $this->expenses($startDate, $endDate, $outletId);
        return match ($slug) {
            'menu-best-seller' => $this->menuBestSeller($startDate, $endDate, $outletId),
            'ingredient-consumption' => $this->ingredientConsumption($startDate, $endDate, $outletId),
            'stock-opname-variance' => $this->stockOpnameVariance($startDate, $endDate, $outletId),
            'payroll-kasbon' => $this->payrollKasbon($startDate, $endDate, $outletId),
            'menu-margin' => $this->menuMargin($startDate, $endDate, $outletId),
            default => abort(404),
        };
    }

    private function menuBestSeller(string $start, string $end, ?int $outletId): array
    {
        $query = DB::table('sales_transaction_items')
            ->join('sales_transactions', 'sales_transactions.id', '=', 'sales_transaction_items.sales_transaction_id')
            ->join('menu_items', 'menu_items.id', '=', 'sales_transaction_items.menu_item_id')
            ->where('sales_transactions.status', 'completed')
            ->whereBetween('sales_transactions.transaction_date', [$start.' 00:00:00', $end.' 23:59:59']);
        $this->scopeOutlet($query, $outletId, 'sales_transactions.outlet_id');
        $sales = $query->selectRaw('menu_items.id as menu_item_id, menu_items.name as menu_name, menu_items.category, SUM(sales_transaction_items.quantity) as qty_sold, SUM(CASE WHEN sales_transactions.subtotal_amount > 0 THEN sales_transaction_items.subtotal * sales_transactions.total_amount / sales_transactions.subtotal_amount ELSE 0 END) as total_revenue')
            ->groupBy('menu_items.id', 'menu_items.name', 'menu_items.category')->orderByDesc('qty_sold')->get();

        $returns = DB::table('sales_return_items')
            ->join('sales_returns', 'sales_returns.id', '=', 'sales_return_items.sales_return_id')
            ->whereBetween('sales_returns.created_at', [$start.' 00:00:00', $end.' 23:59:59']);
        $this->scopeOutlet($returns, $outletId, 'sales_returns.outlet_id');
        $returns = $returns->selectRaw('sales_return_items.menu_item_id, SUM(sales_return_items.quantity) as ret_qty, SUM(sales_return_items.subtotal) as ret_rev')
            ->groupBy('sales_return_items.menu_item_id')->get()->keyBy('menu_item_id');
        $rows = $sales->map(fn ($row) => ['menu_name' => $row->menu_name, 'category' => $row->category, 'qty_sold' => max(0, (int) $row->qty_sold - (int) ($returns[$row->menu_item_id]->ret_qty ?? 0)), 'total_revenue' => max(0, (float) $row->total_revenue - (float) ($returns[$row->menu_item_id]->ret_rev ?? 0))]);
        return ['summary' => [['label' => 'Total Qty Terjual', 'value' => number_format((int) $rows->sum('qty_sold'))], ['label' => 'Total Omzet', 'value' => $this->rupiah($rows->sum('total_revenue'))], ['label' => 'Jumlah Menu Terjual', 'value' => number_format($rows->count())]], 'rows' => $rows->values()->all()];
    }

    private function ingredientConsumption(string $start, string $end, ?int $outletId): array
    {
        $query = DB::table('stock_movements')->whereBetween('created_at', [$start.' 00:00:00', $end.' 23:59:59'])->whereIn('type', ['sale_deduction', 'sale_return', 'expired', 'reject', 'transfer_out']);
        $this->scopeOutlet($query, $outletId, 'outlet_id');
        $movements = $query->selectRaw('ingredient_id, type, SUM(quantity) as total_qty')->groupBy('ingredient_id', 'type')->get();
        $ingredients = Ingredient::query()->get(['id', 'name', 'unit', 'cost_per_unit'])->keyBy('id');
        $rows = $movements->groupBy('ingredient_id')->map(function (Collection $items, $id) use ($ingredients) {
            $ingredient = $ingredients->get($id); $types = $items->pluck('total_qty', 'type');
            $sale = max(0, -((float) ($types['sale_deduction'] ?? 0)) - (float) ($types['sale_return'] ?? 0));
            $waste = abs((float) ($types['expired'] ?? 0)) + abs((float) ($types['reject'] ?? 0)); $transfer = abs((float) ($types['transfer_out'] ?? 0)); $total = $sale + $waste + $transfer;
            return ['ingredient_name' => $ingredient?->name ?? '—', 'unit' => $ingredient?->unit ?? '', 'sale_qty' => $sale, 'waste_qty' => $waste, 'transfer_out_qty' => $transfer, 'total_out_qty' => $total, 'est_value' => $total * (float) ($ingredient?->cost_per_unit ?? 0)];
        })->sortByDesc('total_out_qty')->values();
        return ['summary' => [['label' => 'Jenis Bahan Terpakai', 'value' => number_format($rows->count())], ['label' => 'Estimasi Nilai Pemakaian', 'value' => $this->rupiah($rows->sum('est_value'))]], 'rows' => $rows->all()];
    }

    private function stockOpnameVariance(string $start, string $end, ?int $outletId): array
    {
        $query = DB::table('stock_opname_items')->join('stock_opnames', 'stock_opnames.id', '=', 'stock_opname_items.stock_opname_id')->where('stock_opnames.status', 'applied')->whereBetween('stock_opnames.opname_date', [$start, $end]);
        $this->scopeOutlet($query, $outletId, 'stock_opnames.outlet_id');
        $items = $query->selectRaw('stock_opname_items.ingredient_id, COUNT(*) as opname_count, SUM(stock_opname_items.difference) as net_difference, SUM(ABS(stock_opname_items.difference)) as abs_difference')->groupBy('stock_opname_items.ingredient_id')->orderByDesc('abs_difference')->get();
        $ingredients = Ingredient::query()->get(['id', 'name', 'unit'])->keyBy('id');
        $rows = $items->map(fn ($row) => ['ingredient_name' => $ingredients[$row->ingredient_id]->name ?? '—', 'unit' => $ingredients[$row->ingredient_id]->unit ?? '', 'opname_count' => (int) $row->opname_count, 'net_difference' => (float) $row->net_difference, 'abs_difference' => (float) $row->abs_difference]);
        $net = (float) $rows->sum('net_difference');
        return ['summary' => [['label' => 'Item Diopname', 'value' => number_format((int) $rows->sum('opname_count'))], ['label' => 'Bahan dengan Selisih', 'value' => number_format($rows->where('abs_difference', '>', 0)->count())], ['label' => 'Selisih Bersih', 'value' => ($net > 0 ? '+' : '').number_format($net, 2)]], 'rows' => $rows->values()->all()];
    }

    private function payrollKasbon(string $start, string $end, ?int $outletId): array
    {
        $query = Payroll::query()->with('outlet')->where('status', 'paid')->whereBetween('pay_date', [$start, $end]);
        $this->scopeOutlet($query, $outletId, 'outlet_id');
        $payrolls = $query->get();
        $payrollRows = $payrolls->groupBy('outlet_id')->map(function (Collection $items) {
            $first = $items->first();
            return ['outlet_name' => $first->outlet?->name ?? '—', 'employee_count' => $items->pluck('employee_id')->unique()->count(), 'total_base' => (float) $items->sum('base_salary'), 'total_bonus' => (float) $items->sum(fn (Payroll $payroll) => (float) $payroll->bonus_masuk + (float) $payroll->bonus_goreng), 'total_kasbon_deduction' => (float) $items->sum('kasbon_deduction'), 'total_paid' => (float) $items->sum('total_salary')];
        })->values();
        $periodQuery = Payroll::query()->with(['outlet', 'employee'])->where('status', 'paid')->whereBetween('pay_date', [$start, $end]);
        $this->scopeOutlet($periodQuery, $outletId, 'outlet_id');
        $periodRows = $periodQuery->orderBy('period_start')->orderBy('outlet_id')->orderBy('employee_id')->get()->map(fn (Payroll $payroll) => [
            'period_start' => $payroll->period_start->toDateString(),
            'period_end' => $payroll->period_end->toDateString(),
            'pay_date' => $payroll->pay_date->toDateString(),
            'outlet_name' => $payroll->outlet?->name ?? '—',
            'employee_name' => $payroll->employee?->name ?? '—',
            'base_salary' => (float) $payroll->base_salary,
            'bonus_masuk' => (float) $payroll->bonus_masuk,
            'bonus_goreng' => (float) $payroll->bonus_goreng,
            'kasbon_deduction' => (float) $payroll->kasbon_deduction,
            'total_salary' => (float) $payroll->total_salary,
        ])->values();
        $employeeQuery = Employee::query()->with('outlet')->where('status', 'active');
        $this->scopeOutlet($employeeQuery, $outletId, 'outlet_id');
        $kasbonRows = $employeeQuery->get()->map(fn (Employee $employee) => ['name' => $employee->name, 'outlet_name' => $employee->outlet?->name ?? '—', 'outstanding' => $employee->outstandingKasbon()])->filter(fn (array $row) => $row['outstanding'] > 0)->sortByDesc('outstanding')->values();
        return ['summary' => [['label' => 'Jumlah Periode', 'value' => $periodRows->pluck('period_start')->unique()->count().' periode'], ['label' => 'Total Gaji Dibayar', 'value' => $this->rupiah($payrollRows->sum('total_paid'))], ['label' => 'Total Bonus', 'value' => $this->rupiah($payrollRows->sum('total_bonus'))], ['label' => 'Potongan Kasbon', 'value' => $this->rupiah($payrollRows->sum('total_kasbon_deduction'))], ['label' => 'Saldo Kasbon Berjalan', 'value' => $this->rupiah($kasbonRows->sum('outstanding'))]], 'rows' => [], 'payrollRows' => $payrollRows->all(), 'payrollByPeriodRows' => $periodRows->all(), 'outstandingKasbonRows' => $kasbonRows->all()];
    }

    private function menuMargin(string $start, string $end, ?int $outletId): array
    {
        $menuItems = MenuItem::query()->with('recipes.ingredient')->where('is_active', true)->get();
        $hpp = $menuItems->mapWithKeys(fn (MenuItem $menu) => [$menu->id => $menu->recipes->sum(fn ($recipe) => (float) $recipe->qty_per_unit * (float) ($recipe->ingredient?->cost_per_unit ?? 0))]);
        $salesQuery = DB::table('sales_transaction_items')->join('sales_transactions', 'sales_transactions.id', '=', 'sales_transaction_items.sales_transaction_id')->where('sales_transactions.status', 'completed')->whereBetween('sales_transactions.transaction_date', [$start.' 00:00:00', $end.' 23:59:59']);
        $this->scopeOutlet($salesQuery, $outletId, 'sales_transactions.outlet_id');
        $sales = $salesQuery->selectRaw('sales_transaction_items.menu_item_id, SUM(sales_transaction_items.quantity) as qty_sold, SUM(CASE WHEN sales_transactions.subtotal_amount > 0 THEN sales_transaction_items.subtotal * sales_transactions.total_amount / sales_transactions.subtotal_amount ELSE 0 END) as revenue')->groupBy('sales_transaction_items.menu_item_id')->get()->keyBy('menu_item_id');
        $returnQuery = DB::table('sales_return_items')->join('sales_returns', 'sales_returns.id', '=', 'sales_return_items.sales_return_id')->whereBetween('sales_returns.created_at', [$start.' 00:00:00', $end.' 23:59:59']);
        $this->scopeOutlet($returnQuery, $outletId, 'sales_returns.outlet_id');
        $returns = $returnQuery->selectRaw('sales_return_items.menu_item_id, SUM(sales_return_items.quantity) as ret_qty, SUM(sales_return_items.subtotal) as ret_rev')->groupBy('sales_return_items.menu_item_id')->get()->keyBy('menu_item_id');
        $rows = $sales->map(function ($sale, $id) use ($menuItems, $hpp, $returns) {
            $menu = $menuItems->firstWhere('id', $id); $unitHpp = (float) ($hpp[$id] ?? 0); $return = $returns[$id] ?? null;
            $qty = max(0, (int) $sale->qty_sold - (int) ($return->ret_qty ?? 0)); $revenue = max(0, (float) $sale->revenue - (float) ($return->ret_rev ?? 0)); $totalHpp = $unitHpp * $qty; $margin = $revenue - $totalHpp;
            return ['menu_name' => $menu?->name ?? '—', 'hpp_per_unit' => $unitHpp, 'price' => (float) ($menu?->price ?? 0), 'margin_per_unit' => (float) ($menu?->price ?? 0) - $unitHpp, 'qty_sold' => $qty, 'revenue' => $revenue, 'total_hpp' => $totalHpp, 'gross_margin' => $margin, 'margin_pct' => $revenue > 0 ? ($margin / $revenue) * 100 : 0];
        })->filter(fn ($row) => $row['qty_sold'] > 0)->sortByDesc('gross_margin')->values();
        $expenseQuery = Expense::whereBetween('expense_date', [$start, $end]); $payrollQuery = Payroll::where('status', 'paid')->whereBetween('pay_date', [$start, $end]);
        $this->scopeOutlet($expenseQuery, $outletId, 'outlet_id'); $this->scopeOutlet($payrollQuery, $outletId, 'outlet_id');
        $expense = (float) $expenseQuery->sum('amount'); $payroll = (float) $payrollQuery->sum('total_salary'); $revenue = (float) $rows->sum('revenue'); $totalHpp = (float) $rows->sum('total_hpp'); $gross = (float) $rows->sum('gross_margin');
        $summary = [['label' => 'Total Omzet', 'value' => $this->rupiah($revenue)], ['label' => 'Total HPP', 'value' => $this->rupiah($totalHpp)], ['label' => 'Laba Kotor', 'value' => $this->rupiah($gross)], ['label' => 'Margin Rata-rata', 'value' => number_format($revenue > 0 ? ($gross / $revenue) * 100 : 0, 1).'%']];
        if ($expense > 0 || $payroll > 0) { $summary[] = ['label' => 'Total Pengeluaran (Kas Kecil)', 'value' => $this->rupiah($expense)]; $summary[] = ['label' => 'Total Gaji Dibayar', 'value' => $this->rupiah($payroll)]; $summary[] = ['label' => 'Laba Bersih (Kotor - Beban)', 'value' => $this->rupiah($gross - $expense - $payroll)]; }
        return ['summary' => $summary, 'rows' => $rows->all()];
    }

    private function scopeOutlet($query, ?int $outletId, string $column): void
    {
        if ($outletId && OutletContext::user()?->isAdmin()) { $query->where($column, $outletId); return; }
        if (! OutletContext::user()?->isAdmin()) { $query->where($column, OutletContext::currentOutletId()); }
    }

    private function salesByOutlet(string $start, string $end, ?int $outletId): array
    {
        $query = SalesTransaction::query()->where('status', 'completed')->whereBetween('transaction_date', [$start.' 00:00:00', $end.' 23:59:59']);
        $returns = SalesReturn::query()->whereBetween('created_at', [$start.' 00:00:00', $end.' 23:59:59']);
        $this->scopeOutlet($query, $outletId, 'outlet_id');
        $this->scopeOutlet($returns, $outletId, 'outlet_id');
        $sales = $query->selectRaw('outlet_id, COUNT(*) trx_count, SUM(total_amount) total_omzet')->groupBy('outlet_id')->get()->keyBy('outlet_id');
        $refunds = $returns->selectRaw('outlet_id, SUM(total_refund) total_retur')->groupBy('outlet_id')->pluck('total_retur', 'outlet_id');
        $names = Outlet::pluck('name', 'id');
        $rows = $sales->keys()->merge($refunds->keys())->unique()->map(function ($id) use ($sales, $refunds, $names) {
            $gross = (float) ($sales[$id]->total_omzet ?? 0);
            $refund = (float) ($refunds[$id] ?? 0);
            $net = $gross - $refund;
            $count = (int) ($sales[$id]->trx_count ?? 0);
            return ['outlet_name' => $names[$id] ?? '—', 'trx_count' => $count, 'total_omzet' => $gross, 'total_retur' => $refund, 'net_omzet' => $net, 'aov' => $count ? $net / $count : 0];
        })->sortByDesc('net_omzet')->values();
        $total = ['trx_count' => $rows->sum('trx_count'), 'total_omzet' => $rows->sum('total_omzet'), 'total_retur' => $rows->sum('total_retur'), 'net_omzet' => $rows->sum('net_omzet')];
        $total['aov'] = $total['trx_count'] ? $total['net_omzet'] / $total['trx_count'] : 0;
        return ['summary' => [['label' => 'Total Omzet (Gross)', 'value' => $this->rupiah($total['total_omzet'])], ['label' => 'Total Retur', 'value' => $this->rupiah($total['total_retur'])], ['label' => 'Omzet Bersih', 'value' => $this->rupiah($total['net_omzet'])], ['label' => 'Jumlah Transaksi', 'value' => number_format($total['trx_count'])], ['label' => 'Rata-rata / Transaksi', 'value' => $this->rupiah($total['aov'])]], 'rows' => $rows->all()];
    }

    private function expenses(string $start, string $end, ?int $outletId): array
    {
        $query = Expense::query()->with('outlet')->whereBetween('expense_date', [$start, $end]);
        $this->scopeOutlet($query, $outletId, 'outlet_id');
        $rows = $query->get()->groupBy(fn ($expense) => $expense->outlet_id.'|'.$expense->category)->map(function (Collection $group) { $first = $group->first(); return ['outlet_name' => $first->outlet?->name ?? '—', 'category' => $first->category, 'trx_count' => $group->count(), 'total_amount' => (float) $group->sum('amount')]; })->sortByDesc('total_amount')->values();
        return ['summary' => [['label' => 'Total Pengeluaran', 'value' => $this->rupiah($rows->sum('total_amount'))], ['label' => 'Jumlah Transaksi', 'value' => number_format($rows->sum('trx_count'))], ['label' => 'Kategori Terbesar', 'value' => ucfirst(str_replace('_', ' ', $rows->groupBy('category')->map(fn ($g) => $g->sum('total_amount'))->sortDesc()->keys()->first() ?? '-'))]], 'rows' => $rows->all(), 'perOutletRows' => $rows->groupBy('outlet_name')->map(fn ($group, $name) => ['outlet_name' => $name, 'trx_count' => $group->sum('trx_count'), 'total_amount' => $group->sum('total_amount')])->values()->all()];
    }

    private function rupiah(float|int $value): string { return 'Rp '.number_format($value, 0, ',', '.'); }
}
