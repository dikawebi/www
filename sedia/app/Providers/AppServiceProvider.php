<?php

namespace App\Providers;

use App\Models\SalesTransaction;
use App\Observers\SalesTransactionObserver;
use App\Support\OutletContext;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
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

        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_START,
            fn () => view('filament.outlet-switcher', [
                'currentOutletId' => OutletContext::currentOutletId(),
            ]),
        );
    }
}
