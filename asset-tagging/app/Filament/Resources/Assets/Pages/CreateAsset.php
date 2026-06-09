<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\AssetSequence;

class CreateAsset extends CreateRecord
{
    protected static string $resource = AssetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $categoryId = $data['category_id'] ?? null;

        if (! $categoryId) {
            throw new \Exception('Proses gagal: Kategori wajib dipilih terlebih dahulu.');
        }

        // 💡 FORCE INJECTION SYSTEM:
        // Mengunci baris SQL sequence dan menggenerasi kode asli secara paksa
        // tepat 1 milidetik sebelum perintah INSERT PostgreSQL dieksekusi.
        $setting = AssetSequence::where('category_id', $categoryId)->lockForUpdate()->first();

        $prefix = $setting ? $setting->prefix : 'AST';
        $nextValue = $setting ? $setting->next_value : 1;
        $padding = $setting ? $setting->padding : 4;
        $format = $setting ? $setting->format : '{prefix}-{year}-{sequence}';

        $sequenceString = str_pad($nextValue, $padding, '0', STR_PAD_LEFT);

        // MENYUNTIKKAN DATA PASTI KE ARRAY DATABASE
        $data['asset_id'] = str_replace(
            ['{prefix}', '{year}', '{sequence}'],
            [$prefix, date('Y'), $sequenceString],
            $format
        );

        // Update nilai counter berikutnya di database
        if ($setting) {
            $setting->next_value = $nextValue + 1;
            $setting->save();
        }

        return $data;
    }
}
