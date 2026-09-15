<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Support\OutletContext;
use App\Support\RolePermission;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InertiaDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = OutletContext::user();
        $canViewSales = RolePermission::can($user, 'SalesTransactionResource', 'view');
        $canViewTopMenus = RolePermission::can($user, 'MenuBestSellerReport', 'view');
        $canViewStocks = RolePermission::can($user, 'StockResource', 'view');

        $trend = collect();
        if ($canViewSales) {
            $start = now()->subDays(6)->startOfDay();
            $trendRows = OutletContext::visibleQuery(SalesTransaction::query())->where('status', 'completed')->where('transaction_date', '>=', $start)->selectRaw('DATE(transaction_date) as sale_date, SUM(total_amount) as revenue')->groupByRaw('DATE(transaction_date)')->pluck('revenue', 'sale_date');
            $trend = collect(range(6, 0))->map(fn ($days) => ['date' => now()->subDays($days)->format('d M'), 'revenue' => (float) ($trendRows[now()->subDays($days)->toDateString()] ?? 0)]);
        }

        $topMenus = collect();
        if ($canViewTopMenus) {
            $topMenus = DB::table('sales_transaction_items')->join('sales_transactions', 'sales_transactions.id', '=', 'sales_transaction_items.sales_transaction_id')->join('menu_items', 'menu_items.id', '=', 'sales_transaction_items.menu_item_id')->where('sales_transactions.status', 'completed')->when(! $user?->isAdmin(), fn ($q) => $user?->outlet_id ? $q->where('sales_transactions.outlet_id', $user->outlet_id) : $q->whereRaw('1 = 0'))->when(OutletContext::currentOutletId(), fn ($q, $outletId) => $q->where('sales_transactions.outlet_id', $outletId))->where('sales_transactions.transaction_date', '>=', now()->subDays(30))->selectRaw('menu_items.name, SUM(sales_transaction_items.quantity) as quantity')->groupBy('menu_items.id', 'menu_items.name')->orderByDesc('quantity')->limit(5)->get();
        }

        $lowStocks = $canViewStocks
            ? OutletContext::visibleQuery(Stock::query())->with(['ingredient:id,name,unit,min_stock', 'outlet:id,name'])->whereHas('ingredient', fn ($q) => $q->whereColumn('stocks.quantity', '<', 'ingredients.min_stock'))->orderBy('quantity')->limit(5)->get()->map(fn ($stock) => ['name' => $stock->ingredient?->name, 'outlet' => $stock->outlet?->name, 'quantity' => (float) $stock->quantity, 'minimum' => (float) $stock->ingredient?->min_stock, 'unit' => $stock->ingredient?->unit])
            : collect();

        return Inertia::render('Dashboard', [
            'stats' => [
                'menus' => RolePermission::can($user, 'MenuItemResource', 'view') ? MenuItem::query()->where('is_active', true)->count() : 0,
                'ingredients' => RolePermission::can($user, 'IngredientResource', 'view') ? Ingredient::query()->where('is_active', true)->count() : 0,
                'transactions' => $canViewSales ? OutletContext::visibleQuery(SalesTransaction::query())->count() : 0,
                'revenue' => $canViewSales ? OutletContext::visibleQuery(SalesTransaction::query())
                    ->where('status', 'completed')
                    ->sum('total_amount') : 0,
            ],
            'trend' => $trend,
            'topMenus' => $topMenus,
            'lowStocks' => $lowStocks,
        ]);
    }
}
