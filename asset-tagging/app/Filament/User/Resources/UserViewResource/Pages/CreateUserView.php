<?php

namespace App\Filament\User\Resources\UserViewResource\Pages;

use App\Filament\User\Resources\UserViewResource;
use App\Models\AssetSequence;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateUserView extends CreateRecord
{
    protected static string $resource = UserViewResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $selectedCategoryId = $data['category_id'] ?? null;

        // Membuka DB Transaction agar aman saat diakses banyak user sekaligus
        DB::transaction(function () use (&$data, $selectedCategoryId) {
            $setting = AssetSequence::where('category_id', $selectedCategoryId)->lockForUpdate()->first();

            // Skenario darurat jika Admin belum menyusun setting kategori terkait
            if (!$setting) {
                $categoryName = \App\Models\Category::find($selectedCategoryId)?->name ?? 'AST';
                $generatedPrefix = strtoupper(substr(str_replace(' ', '', $categoryName), 0, 3));

                $setting = AssetSequence::create([
                    'category_id' => $selectedCategoryId,
                    'prefix' => $generatedPrefix,
                    'format' => '{prefix}-{year}-{sequence}',
                    'next_value' => 1,
                    'padding' => 4
                ]);
            }

            $tahun = date('Y');
            $sequenceString = str_pad($setting->next_value, $setting->padding, '0', STR_PAD_LEFT);

            $finalId = str_replace(
                ['{prefix}', '{year}', '{sequence}'],
                [$setting->prefix, $tahun, $sequenceString],
                $setting->format
            );

            // Suntikkan nomor seri urut resmi ke kolom asset_id
            $data['asset_id'] = $finalId;

            // Amankan antrean: Naikkan angka counter +1 di database untuk user berikutnya
            $setting->increment('next_value');
        });

        return $data;
    }

    protected function makeSchema(): \Filament\Schemas\Schema
    {
        return $this->getResource()::schema(
            parent::makeSchema()->operation('create')
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
