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
    /**
     * Jumlah asset bisa diatur saat memanggil seeder:
     * php artisan db:seed --class=AssetSeeder --force
     */
    public function run(int $count = 60): void
    {
        $brands = Brand::pluck('id', 'name');
        $categories = Category::pluck('id', 'name');
        $locationIds = Location::pluck('id')->all();
        $departmentIds = Department::pluck('id')->all();

        // Template nama aset per kategori: [nama, brand]
        $catalog = [
            'Laptop' => [
                ['ThinkPad X1 Carbon Gen 11', 'Lenovo'],
                ['ThinkPad T14s', 'Lenovo'],
                ['MacBook Pro 14 M3', 'Apple'],
                ['MacBook Air 13 M2', 'Apple'],
                ['Latitude 5420', 'Dell'],
                ['Vostro 3520', 'Dell'],
                ['ProBook 450 G10', 'HP'],
                ['Zenbook 14 OLED', 'Asus'],
            ],
            'Monitor' => [
                ['UltraFine 24 Inch 4K', 'Apple'],
                ['ProArt Display PA278CV', 'Asus'],
                ['UltraSharp U2723QE', 'Dell'],
                ['ThinkVision P27h', 'Lenovo'],
                ['Z27k G3', 'HP'],
            ],
            'Printer' => [
                ['LaserJet Pro M404dn', 'HP'],
                ['EcoTank L3210 All-in-One', 'Epson'],
            ],
            'Server' => [
                ['PowerEdge R750', 'Dell'],
                ['ProLiant DL380 Gen10', 'HP'],
                ['ThinkSystem SR650', 'Lenovo'],
            ],
            'Peripherals' => [
                ['Catalyst Switch 24-Port', 'Cisco'],
                ['Cloud Core Router CCR2004', 'MikroTik'],
                ['Docking Station WD19', 'Dell'],
                ['Wireless Keyboard & Mouse MK540', 'Logitech'],
            ],
        ];

        $statuses = ['In use', 'In use', 'In use', 'Idle', 'Broke'];
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $sequence = Asset::whereYear('created_at', now()->year)->max('id') ?? 0;
        $users = ['Andi Wijaya', 'Budi Santoso', 'Citra Lestari', 'Dewi Putri', null, null];

        // Seeder bypass observer (observer menuntut user login + sequence departemen)
        Asset::withoutEvents(fn () => collect(range(1, $count))->each(function ($i) use ($brands, $categories, $locationIds, $departmentIds, $catalog, $statuses, $users, $alphabet, $sequence) {
            $categoryName = array_rand($catalog);
            [$name, $brandName] = $catalog[$categoryName][array_rand($catalog[$categoryName])];

            $assetId = sprintf(
                'AST-%s-%06d',
                now()->format('Y'),
                ($sequence + $i)
            );

            Asset::firstOrCreate(
                ['asset_id' => $assetId],
                [
                    'name' => $name,
                    'category_id' => $categories[$categoryName],
                    'brand_id' => $brands[$brandName] ?? $brands->first(),
                    'serial_number' => sprintf(
                        'SN-%s%s-%05d-%s%s',
                        $alphabet[random_int(0, 25)],
                        $alphabet[random_int(0, 25)],
                        random_int(10000, 99999),
                        $alphabet[random_int(0, 25)],
                        $alphabet[random_int(0, 25)]
                    ),
                    'location_id' => $locationIds[array_rand($locationIds)],
                    'department_id' => $departmentIds[array_rand($departmentIds)],
                    'pr_number' => 'PR-' . random_int(2026001, 2026999),
                    'po_number' => 'PO-' . random_int(2026001, 2026999),
                    'user_name' => $users[array_rand($users)],
                    'status' => $statuses[array_rand($statuses)],
                    'images' => null,
                ]
            );
        }));

        $this->command->info("Berhasil menyiapkan {$count} asset untuk bulk print QR.");
    }
}
