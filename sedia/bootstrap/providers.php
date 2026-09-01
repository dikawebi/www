<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\DashboardPanelProvider;
use App\Providers\Filament\PosPanelProvider;
use App\Providers\ObserverServiceProvider;

return [
    AppServiceProvider::class,
    DashboardPanelProvider::class,
    PosPanelProvider::class,
    ObserverServiceProvider::class,
];
