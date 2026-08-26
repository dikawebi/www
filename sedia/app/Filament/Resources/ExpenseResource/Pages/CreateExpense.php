<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use App\Support\OutletContext;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        // Staff paksa outlet_id ke outlet sendiri
        if (! OutletContext::user()?->isAdmin()) {
            $data['outlet_id'] = OutletContext::currentOutletId();
        }

        return $data;
    }
}
