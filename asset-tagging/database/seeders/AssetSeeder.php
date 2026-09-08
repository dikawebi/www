<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $categoryId = Category::first()->id;
        $locationId = Location::first()->id;
        $departmentId = Department::where('name', 'IT')->first()->id;
        $brandId = Brand::first()->id;

        Asset::withoutEvents(function () use ($categoryId, $locationId, $departmentId, $brandId) {
            for ($i = 3; $i <= 200; $i++) {
                $assetId = sprintf('IT-%s-%04d', date('Y'), $i);

                Asset::create([
                    'asset_id'     => $assetId,
                    'name'         => '-',
                    'category_id'  => $categoryId,
                    'location_id'  => $locationId,
                    'department_id'=> $departmentId,
                    'brand_id'     => $brandId,
                    'status'       => 'Idle',
                    'pr_number'    => null,
                    'po_number'    => null,
                    'user_name'    => null,
                    'images'       => null,
                ]);
            }
        });

        $this->command->info('Berhasil membuat 198 asset: IT-' . date('Y') . '-0003 s/d IT-' . date('Y') . '-0200');
    }
}
