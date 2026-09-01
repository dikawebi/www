<?php

namespace App\Providers\Filament;

use App\Filament\Pages\ManageBranding;
use App\Filament\Pages\ManageRolePermissions;
use App\Filament\Pages\PosShortcut;
use App\Filament\Pages\Reports\ExpenseReport;
use App\Filament\Pages\Reports\IngredientConsumptionReport;
use App\Filament\Pages\Reports\MenuBestSellerReport;
use App\Filament\Pages\Reports\MenuMarginReport;
use App\Filament\Pages\Reports\PayrollKasbonReport;
use App\Filament\Pages\Reports\SalesByOutletReport;
use App\Filament\Pages\Reports\StockOpnameVarianceReport;
use App\Filament\Pages\SaranReorder;
use App\Filament\Pages\TutupKasirHarian;
use App\Filament\Widgets\LowStockTableWidget;
use App\Filament\Widgets\RevenueOverviewWidget;
use App\Filament\Widgets\SalesTrendChartWidget;
use App\Support\Branding;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DashboardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('dashboard')
            ->path('dashboard')
            ->login()
            ->brandName(fn () => Branding::appName())
            ->brandLogo(fn () => new HtmlString(
                ($logo = Branding::appLogoUrl())
                    ? '<div style="display:flex;align-items:center;gap:10px"><img src="'.e($logo).'" alt="'.e(Branding::appName()).' logo" style="height:42px;width:auto;object-fit:contain;border-radius:6px;flex-shrink:0"><span style="font-weight:800;font-size:14.5px;letter-spacing:-0.01em;line-height:1;white-space:nowrap">'.e(Branding::appName()).'</span></div>'
                    : '<span style="font-weight:800;font-size:15px;letter-spacing:-0.01em">'.e(Branding::appName()).'</span>'
            ))
            ->brandLogoHeight('42px')
            ->favicon(fn () => Branding::faviconUrl())
            ->colors(fn () => ['primary' => Color::hex(Branding::primaryColor()) ?: Color::Amber])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->pages([
                Dashboard::class,
                PosShortcut::class,
                TutupKasirHarian::class,
                SaranReorder::class,
                ManageRolePermissions::class,
                ManageBranding::class,
                SalesByOutletReport::class,
                MenuBestSellerReport::class,
                IngredientConsumptionReport::class,
                StockOpnameVarianceReport::class,
                PayrollKasbonReport::class,
                MenuMarginReport::class,
                ExpenseReport::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Penjualan'),
                NavigationGroup::make('Produk'),
                NavigationGroup::make('Persediaan'),
                NavigationGroup::make('Operasional'),
                NavigationGroup::make('Laporan'),
                NavigationGroup::make('Pengaturan'),
            ])
            ->spa()
            ->unsavedChangesAlerts()
            ->globalSearch(true)
            ->databaseNotifications()
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
                RevenueOverviewWidget::class,
                SalesTrendChartWidget::class,
                LowStockTableWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
