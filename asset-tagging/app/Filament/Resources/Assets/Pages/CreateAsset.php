<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetResource;
use App\Models\AssetSequence;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAsset extends CreateRecord
{
    protected static string $resource = AssetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $setting = AssetSequence::where('department_id', $user->department_id)->first();

        if ($setting) {
            // Generate format ID
            $sequenceString = str_pad($setting->next_value, $setting->padding, '0', STR_PAD_LEFT);
            $data['asset_id'] = str_replace(
                ['{prefix}', '{year}', '{sequence}'],
                [$setting->prefix, date('Y'), $sequenceString],
                $setting->format
            );

            // Update sequence di database agar tidak bentrok
            $setting->increment('next_value');
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        // Ini akan mengarahkan kembali ke halaman 'index' (ListAssets)
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationMessage(): ?string
    {
        return 'Data aset berhasil disimpan!';
    }
}
