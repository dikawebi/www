<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeTransaction;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\MenuRecipe;
use App\Models\Outlet;
use App\Models\Payroll;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DkriukSeeder extends Seeder
{
    /**
     * Seed data asli untuk klien D'Kriuk: reset outlet & menu lama, lalu buat
     * outlet, akun login karyawan, bahan baku, dan menu yang baru.
     *
     * PERINGATAN: run() ini akan MENGHAPUS SEMUA outlet & menu yang ada
     * sekarang (termasuk data dummy contoh dari SampleDataSeeder), beserta
     * semua data transaksi yang menempel ke outlet/menu tersebut (penjualan,
     * stok, stock opname, transfer stok, kasbon/penggajian karyawan). Bahan
     * baku (Ingredient) dan akun User lama TIDAK dihapus. Jangan jalankan
     * ulang begitu outlet Sempu/Kasuari sudah dipakai transaksi sungguhan,
     * karena akan ikut terhapus juga.
     *
     * CATATAN: harga menu masih diisi 0 (placeholder) karena belum ada daftar
     * harga dari klien — tolong update lewat menu "Menu" di Filament sebelum
     * dipakai transaksi sungguhan. Unit bahan baku juga masih default 'pcs',
     * silakan sesuaikan lewat menu "Bahan baku" kalau perlu satuan lain.
     * Password login karyawan di-set default 'password' — sarankan ganti
     * setelah login pertama.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->resetOldOutletAndMenuData();

            // 1. Outlets
            $sempu = Outlet::firstOrCreate(
                ['name' => 'Sempu'],
                ['is_active' => true],
            );

            $kasuari = Outlet::firstOrCreate(
                ['name' => 'Kasuari'],
                ['is_active' => true],
            );

            // 2. Karyawan + akun login masing-masing
            $this->createEmployeeWithLogin(
                name: 'Rasmi',
                email: 'rasmi@dkriuk.local',
                outlet: $sempu,
            );

            $this->createEmployeeWithLogin(
                name: 'Ajiz',
                email: 'ajiz@dkriuk.local',
                outlet: $kasuari,
            );

            // 3. Bahan baku (stock inventory)
            $items = [
                'Ayam Original' => 'Ayam',
                'Ayam Spicy' => 'Ayam',
                'Nasi' => 'Nasi',
                'Kulit' => 'Pelengkap',
                'Sate' => 'Pelengkap',
                'Sambel Geprek' => 'Sambal',
                'Saos Keju' => 'Saos',
                'Saos BBQ' => 'Saos',
                'Saos Dkribho' => 'Saos',
                'Saos BlackPaper' => 'Saos',
            ];

            foreach ($items as $name => $category) {
                $ingredient = Ingredient::firstOrCreate(
                    ['name' => $name],
                    ['unit' => 'pcs', 'min_stock' => 10, 'is_active' => true],
                );

                // 4. Menu (item yang sama juga dijual langsung sebagai menu,
                // dengan resep 1:1 ke bahan baku bernama sama sehingga stok
                // otomatis terpotong 1 saat menu ini terjual).
                $menuItem = MenuItem::firstOrCreate(
                    ['name' => $name],
                    ['category' => $category, 'price' => 0, 'is_active' => true],
                );

                MenuRecipe::firstOrCreate(
                    ['menu_item_id' => $menuItem->id, 'ingredient_id' => $ingredient->id],
                    ['qty_per_unit' => 1],
                );
            }
        });
    }

    /**
     * Hapus semua outlet & menu lama beserta data turunannya, dengan urutan
     * yang aman terhadap foreign key (anak dihapus dulu sebelum induknya).
     * Ingredient & User TIDAK ikut dihapus di sini.
     */
    private function resetOldOutletAndMenuData(): void
    {
        // Cascade dari sales_transaction akan otomatis hapus sales_transaction_items.
        SalesTransaction::query()->delete();

        // Cascade dari stock_opname akan otomatis hapus stock_opname_items.
        StockOpname::query()->delete();

        // Cascade dari stock_transfer akan otomatis hapus stock_transfer_items.
        StockTransfer::query()->delete();

        StockMovement::query()->delete();
        Stock::query()->delete();

        Payroll::query()->delete();
        EmployeeTransaction::query()->delete();
        Employee::query()->delete();

        MenuRecipe::query()->delete();
        MenuItem::query()->delete();

        Outlet::query()->delete();
    }

    private function createEmployeeWithLogin(string $name, string $email, Outlet $outlet): Employee
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'role' => 'staff',
                'outlet_id' => $outlet->id,
            ],
        );

        return Employee::firstOrCreate(
            ['name' => $name, 'outlet_id' => $outlet->id],
            ['user_id' => $user->id, 'status' => 'active', 'base_salary' => 0],
        );
    }
}
