<?php

namespace App\Providers;

use App\Models\SalesTransaction;
use App\Observers\SalesTransactionObserver;
use App\Support\OutletContext;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        SalesTransaction::observe(SalesTransactionObserver::class);

        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_START,
            fn () => view('filament.outlet-switcher', [
                'currentOutletId' => OutletContext::currentOutletId(),
            ]),
        );
    }
}
