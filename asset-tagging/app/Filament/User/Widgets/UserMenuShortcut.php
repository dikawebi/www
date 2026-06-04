<?php

namespace App\Filament\User\Widgets;

use Filament\Widgets\Widget;

class UserMenuShortcut extends Widget
{
    // PERBAIKAN: Hapus kata 'static', cukup gunakan 'protected string'
    protected string $view = 'filament.user.widgets.user-menu-shortcut';

    // Membuat widget memenuhi baris penuh di dashboard
    protected int | string | array $columnSpan = 'full';
}
