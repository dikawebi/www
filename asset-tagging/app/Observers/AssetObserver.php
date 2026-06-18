<?php

namespace App\Observers;

use App\Models\Asset;
use App\Models\AssetSequence;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AssetObserver
{
    public function creating(Asset $asset): void
    {
        $user = Auth::user();

        // 1. Cek apakah user punya departemen
        if (!$user || !$user->department_id) {
            throw ValidationException::withMessages([
                'data.department_id' => 'User harus memiliki departemen untuk membuat aset.',
            ]);
        }

        // 2. Cek apakah sequence untuk departemen ini sudah diatur
        $setting = AssetSequence::where('department_id', $user->department_id)->first();

        if (!$setting) {
            throw ValidationException::withMessages([
                'asset_id' => 'Pengaturan nomor urut (sequence) untuk departemen Anda belum dibuat. Silakan hubungi Admin.',
            ]);
        }

        // 3. Lanjutkan generate ID
        $sequenceString = str_pad($setting->next_value, $setting->padding, '0', STR_PAD_LEFT);
        $newId = str_replace(
            ['{prefix}', '{year}', '{sequence}'],
            [$setting->prefix, date('Y'), $sequenceString],
            $setting->format
        );

        $asset->asset_id = $newId;
        $setting->increment('next_value');
    }
}
