<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeTransaction;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\MenuRecipe;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Outlets
        $stockist = Outlet::create([
            'name' => 'Gudang Pusat (Stockist)',
            'address' => 'Jl. Industri Utama No. 1, Jakarta',
            'phone' => '08111111111',
            'is_active' => true,
        ]);

        $outletSudirman = Outlet::create([
            'name' => 'Outlet Sudirman',
            'address' => 'Jl. Jend. Sudirman No. 45, Jakarta',
            'phone' => '08222222222',
            'is_active' => true,
        ]);

        $outletBandung = Outlet::create([
            'name' => 'Outlet Bandung',
            'address' => 'Jl. Riau No. 12, Bandung',
            'phone' => '08333333333',
            'is_active' => true,
        ]);

        // 2. Users
        $admin = User::create([
            'name' => 'Admin Utama',
            'email' => 'admin@sedia.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'outlet_id' => null,
        ]);

        $staffSudirman = User::create([
            'name' => 'Staff Sudirman',
            'email' => 'staff.sudirman@sedia.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'outlet_id' => $outletSudirman->id,
        ]);

        $staffBandung = User::create([
            'name' => 'Staff Bandung',
            'email' => 'staff.bandung@sedia.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'outlet_id' => $outletBandung->id,
        ]);

        // 3. Ingredients
        $ingKopi = Ingredient::create(['name' => 'Biji Kopi Arabika', 'unit' => 'kg', 'min_stock' => 5]);
        $ingSusu = Ingredient::create(['name' => 'Susu UHT Fresh', 'unit' => 'liter', 'min_stock' => 10]);
        $ingGula = Ingredient::create(['name' => 'Sirup Gula Aren', 'unit' => 'liter', 'min_stock' => 5]);
        $ingCup = Ingredient::create(['name' => 'Cup Plastik 16oz', 'unit' => 'pcs', 'min_stock' => 50]);
        $ingStraw = Ingredient::create(['name' => 'Sedotan Steril', 'unit' => 'pcs', 'min_stock' => 50]);

        // 4. Menu Items
        $menuEsKopi = MenuItem::create(['name' => 'Es Kopi Susu Aren', 'category' => 'Coffee', 'price' => 20000, 'is_active' => true]);
        $menuLatte = MenuItem::create(['name' => 'Iced Cafe Latte', 'category' => 'Coffee', 'price' => 24000, 'is_active' => true]);
        $menuEspresso = MenuItem::create(['name' => 'Hot Espresso Single', 'category' => 'Coffee', 'price' => 15000, 'is_active' => true]);

        // 5. Menu Recipes
        MenuRecipe::create(['menu_item_id' => $menuEsKopi->id, 'ingredient_id' => $ingKopi->id, 'qty_per_unit' => 0.018]);
        MenuRecipe::create(['menu_item_id' => $menuEsKopi->id, 'ingredient_id' => $ingSusu->id, 'qty_per_unit' => 0.120]);
        MenuRecipe::create(['menu_item_id' => $menuEsKopi->id, 'ingredient_id' => $ingGula->id, 'qty_per_unit' => 0.030]);
        MenuRecipe::create(['menu_item_id' => $menuEsKopi->id, 'ingredient_id' => $ingCup->id, 'qty_per_unit' => 1]);
        MenuRecipe::create(['menu_item_id' => $menuEsKopi->id, 'ingredient_id' => $ingStraw->id, 'qty_per_unit' => 1]);

        MenuRecipe::create(['menu_item_id' => $menuLatte->id, 'ingredient_id' => $ingKopi->id, 'qty_per_unit' => 0.018]);
        MenuRecipe::create(['menu_item_id' => $menuLatte->id, 'ingredient_id' => $ingSusu->id, 'qty_per_unit' => 0.150]);
        MenuRecipe::create(['menu_item_id' => $menuLatte->id, 'ingredient_id' => $ingCup->id, 'qty_per_unit' => 1]);
        MenuRecipe::create(['menu_item_id' => $menuLatte->id, 'ingredient_id' => $ingStraw->id, 'qty_per_unit' => 1]);

        MenuRecipe::create(['menu_item_id' => $menuEspresso->id, 'ingredient_id' => $ingKopi->id, 'qty_per_unit' => 0.018]);
        MenuRecipe::create(['menu_item_id' => $menuEspresso->id, 'ingredient_id' => $ingCup->id, 'qty_per_unit' => 1]);

        // 6. Employees
        $emp1 = Employee::create([
            'outlet_id' => $outletSudirman->id,
            'user_id' => $staffSudirman->id,
            'name' => 'Budi Santoso',
            'phone' => '08123456789',
            'position' => 'Barista Lead',
            'base_salary' => 3500000,
            'join_date' => '2023-01-15',
            'status' => 'active',
        ]);

        $emp2 = Employee::create([
            'outlet_id' => $outletSudirman->id,
            'user_id' => null,
            'name' => 'Siti Aminah',
            'phone' => '08987654321',
            'position' => 'Kasir',
            'base_salary' => 3000000,
            'join_date' => '2023-06-01',
            'status' => 'active',
        ]);

        $emp3 = Employee::create([
            'outlet_id' => $outletBandung->id,
            'user_id' => $staffBandung->id,
            'name' => 'Asep Kurnia',
            'phone' => '08555555555',
            'position' => 'Head Store',
            'base_salary' => 4000000,
            'join_date' => '2022-11-01',
            'status' => 'active',
        ]);

        $this->call(SalesAndStockSeeder::class);
    }
}
