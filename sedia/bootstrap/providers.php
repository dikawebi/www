<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\DashboardPanelProvider;
use App\Providers\ObserverServiceProvider;

return [
    AppServiceProvider::class,
    DashboardPanelProvider::class,
    ObserverServiceProvider::class,
];
