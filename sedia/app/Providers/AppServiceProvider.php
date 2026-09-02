<?php

namespace App\Providers;

use App\Models\Employee;
use App\Models\EmployeeTransaction;
use App\Models\Expense;
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
use App\Support\Branding;
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
        foreach ([SalesTransaction::class, EmployeeTransaction::class, Payroll::class, StockTransfer::class, StockOpname::class, MenuItem::class, Ingredient::class, User::class, Outlet::class, Employee::class, Expense::class] as $model) {
            $model::observe(ActivityObserver::class);
        }

        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_END,
            fn () => view('filament.outlet-switcher', [
                'currentOutletId' => OutletContext::currentOutletId(),
            ]),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_START,
            fn () => Blade::render(
                '<link rel="manifest" href="/manifest.json">'
                .'<meta name="theme-color" content="'.e(Branding::primaryColor()).'">'
                .'<meta name="apple-mobile-web-app-capable" content="yes">'
                .'<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">'
                .'<meta name="apple-mobile-web-app-title" content="'.e(Branding::appName()).'">'
                .'<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">'
                .'<link rel="icon" type="image/png" href="/icons/icon-192.png">'
            ),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn () => Blade::render(
                '<style>
                    #sedia-loader{position:fixed;inset:0;z-index:99999;display:none;align-items:center;justify-content:center;background:rgba(255,255,255,0.7);backdrop-filter:blur(2px)}
                    .dark #sedia-loader{background:rgba(17,24,39,0.7)}
                    #sedia-loader .spinner{width:32px;height:32px;border:3px solid #e5e7eb;border-top-color:#f59e0b;border-radius:50%;animation:spin .6s linear infinite}
                    #sedia-loader .spinner-text{margin-top:10px;font-size:12px;color:#6b7280;font-weight:600;text-align:center}
                    @keyframes spin{to{transform:rotate(360deg)}}
                </style>
                <div id="sedia-loader"><div class="spinner"></div><div class="spinner-text">Memproses...</div></div>
                <script>
                    (function(){
                        var loader=document.getElementById("sedia-loader");
                        var count=0;
                        document.addEventListener("livewire:message.loading",function(){count++;if(loader)loader.style.display="flex"});
                        document.addEventListener("livewire:message.sent",function(){});
                        document.addEventListener("livewire:message.received",function(){count--;if(count<=0){count=0;if(loader)loader.style.display="none"}});
                        document.addEventListener("livewire:message.error",function(){count=0;if(loader)loader.style.display="none"});
                    })();
                </script>
                <script>if("serviceWorker" in navigator){navigator.serviceWorker.register("/sw.js").catch(()=>{});}</script>'
            ),
        );
    }
}
