<?php

namespace App\Providers;

use App\Models\Employee;
use App\Models\EmployeeTransaction;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\Outlet;
use App\Models\Payroll;
use App\Models\SalesTransaction;
use App\Models\StockOpname;
use App\Models\StockTransfer;
use App\Models\User;
use App\Observers\ActivityObserver;
use App\Observers\SalesTransactionObserver;
use App\Support\OutletContext;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Vercel (dan platform serverless/proxy lain) meneruskan request ke PHP
        // TANPA memberi tahu Laravel bahwa request aslinya HTTPS — kalau tidak
        // dipaksa, semua URL yang di-generate (asset(), font, CSS/JS Filament)
        // jadi http://, dan browser BLOKIR resource http:// di halaman https://
        // (mixed content). Ini yang bikin CSS/JS hilang total di production.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        SalesTransaction::observe(SalesTransactionObserver::class);

        // Activity log untuk model bisnis utama (tanpa Stock/StockMovement agar tidak bising)
        foreach ([SalesTransaction::class, EmployeeTransaction::class, Payroll::class, StockTransfer::class, StockOpname::class, MenuItem::class, Ingredient::class, User::class, Outlet::class, Employee::class] as $model) {
            $model::observe(ActivityObserver::class);
        }

        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_START,
            fn () => view('filament.outlet-switcher', [
                'currentOutletId' => OutletContext::currentOutletId(),
            ]),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_START,
            fn () => Blade::render('<link rel="manifest" href="/manifest.json"><meta name="theme-color" content="#f59e0b"><link rel="apple-touch-icon" href="/favicon.ico">'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn () => Blade::render('<script>if("serviceWorker" in navigator){navigator.serviceWorker.register("/sw.js").catch(()=>{});}</script>'),
        );
    }
}
