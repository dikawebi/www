<?php

namespace App\Filament\User\Widgets;

use Filament\Widgets\Widget;

class UserMenuShortcut extends Widget
{
    protected string $view = 'filament.user.widgets.user-menu-shortcut';

    // Membuat widget melebar penuh memenuhi halaman depan dashboard
    protected int | string | array $columnSpan = 'full';
}
