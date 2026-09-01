<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Pos\ClosingReport;
use App\Filament\Pages\Pos\DashboardShortcut;
use App\Filament\Pages\Pos\Pos;
use App\Filament\Pages\Pos\TransactionHistory;
use App\Support\Branding;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PosPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('pos')
            ->path('pos')
            ->login()
            ->brandName(fn () => Branding::appName())
            ->brandLogo(fn () => Branding::appLogoUrl())
            ->brandLogoHeight('32px')
            ->favicon(fn () => Branding::faviconUrl())
            ->colors(fn () => ['primary' => Color::hex(Branding::primaryColor())])
            ->unsavedChangesAlerts()
            ->databaseNotifications()
            ->navigationGroups([
                NavigationGroup::make()->label('Kasir'),
            ])
            ->pages([
                Pos::class,
                TransactionHistory::class,
                ClosingReport::class,
                DashboardShortcut::class,
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
