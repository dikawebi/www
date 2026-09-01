<?php

namespace App\Filament\Pages\Pos;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class DashboardShortcut extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static string|UnitEnum|null $navigationGroup = 'Kasir';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard';

    protected string $view = 'filament.pages.redirect-dashboard';
}
