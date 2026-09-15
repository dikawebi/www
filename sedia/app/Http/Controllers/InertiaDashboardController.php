<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\SalesTransaction;
use App\Support\OutletContext;
use Inertia\Inertia;
use Inertia\Response;

class InertiaDashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'menus' => MenuItem::query()->where('is_active', true)->count(),
                'ingredients' => Ingredient::query()->where('is_active', true)->count(),
                'transactions' => OutletContext::visibleQuery(SalesTransaction::query())->count(),
                'revenue' => OutletContext::visibleQuery(SalesTransaction::query())
                    ->where('status', 'completed')
                    ->sum('total_amount'),
            ],
        ]);
    }
}
