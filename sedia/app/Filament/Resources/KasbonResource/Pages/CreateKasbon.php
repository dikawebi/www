<?php

namespace App\Filament\Resources\KasbonResource\Pages;

use App\Filament\Resources\KasbonResource;
use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Pages\CreateRecord;

class CreateKasbon extends CreateRecord
{
    protected static string $resource = KasbonResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;
        if ($record->type === 'kasbon' && $record->status === 'pending') {
            $admins = User::where('role', 'admin')->get();
            if ($admins->isNotEmpty()) {
                FilamentNotification::make()
                    ->title('Kasbon baru menunggu persetujuan')
                    ->body($record->employee->name.' — Rp '.number_format($record->amount, 0, ',', '.'))
                    ->sendToDatabase($admins);
            }
        }
    }
}
