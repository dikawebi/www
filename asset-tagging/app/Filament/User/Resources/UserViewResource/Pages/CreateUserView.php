<?php

namespace App\Filament\User\Resources\UserViewResource\Pages;

use App\Filament\User\Resources\UserViewResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserView extends CreateRecord
{
    protected static string $resource = UserViewResource::class;
}
