<?php

namespace App\Filament\User\Resources\UserViewResource\Pages;

use App\Filament\User\Resources\UserViewResource;
use Filament\Resources\Pages\ListRecords;

class ListUserViews extends ListRecords
{
    protected static string $resource = UserViewResource::class;

    protected function getHeaderActions(): array
    {
        return []; // Dikosongkan agar tombol "Create New" hilang dari sisi user umum
    }
}
