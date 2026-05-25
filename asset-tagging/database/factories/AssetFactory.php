<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        // Format ID Aset unik otomatis, contoh: AST-2026-847392
        $assetId = 'AST-' . date('Y') . '-' . $this->faker->unique()->numberBetween(100000, 999999);

        return [
            'asset_id' => $assetId,
            'name' => $this->faker->randomElement([
                'ThinkPad X1 Carbon', 'MacBook Pro M3', 'Dell Latitude 5420',
                'HP LaserJet Pro M404dn', 'Epson L3210 All-in-One',
                'LG UltraFine 24 Inch', 'ASUS ProArt Display 27 Inch',
                'Cisco Catalyst Switch 24-Port', 'MikroTik Cloud Core Router'
            ]),
            // Mengambil ID acak dari data master yang sudah ada nanti
            'category_id' => Category::inRandomOrder()->first()?->id ?? 1,
            'location_id' => Location::inRandomOrder()->first()?->id ?? 1,
            'department_id' => Department::inRandomOrder()->first()?->id ?? 1,

            'pr_number' => 'PR-' . $this->faker->numberBetween(2026001, 2026999),
            'po_number' => 'PO-' . $this->faker->numberBetween(2026001, 2026999),
            'user_name' => $this->faker->name(),
            'status' => $this->faker->randomElement(['In use', 'Idle', 'Broke']),

            // Mengisi array kosong untuk gambar produk sementara
            'images' => null,
        ];
    }
}
