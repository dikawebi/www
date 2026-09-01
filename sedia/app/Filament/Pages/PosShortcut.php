<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class PosShortcut extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static string|UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Buka POS';

    protected static ?string $title = 'POS Kasir';

    protected string $view = 'filament.pages.redirect-pos';
}
