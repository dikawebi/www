<?php

namespace App\Filament\User\Resources\UserViewResource\Pages;

use App\Filament\User\Resources\UserViewResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord; // Pastikan menggunakan core ViewRecord v4
use Illuminate\Support\Facades\Auth;

class ViewUserView extends ViewRecord
{
    protected static string $resource = UserViewResource::class;

    /**
     * Di Filament v4, kita wajib memberi tahu halaman ViewRecord bahwa
     * komponen yang ia render bersumber dari skema 'infolist' di Resource.
     */
    protected function makeInfolist(): \Filament\Schemas\Schema
    {
        return $this->getResource()::infolist(
            $this->makeSchema()->operation('view')
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit_in_admin')
                ->label('Edit Data (Admin)')
                ->color('warning')
                ->icon('heroicon-o-pencil-square')
                ->url(fn ($record) => "/administrator/assets/{$record->id}/edit")
                ->visible(fn () => Auth::user()?->role === 'admin'),
        ];
    }
}
