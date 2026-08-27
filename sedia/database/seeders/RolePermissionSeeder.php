<?php

namespace Database\Seeders;

use App\Models\RolePermission;
use App\Support\RolePermission as RolePermissionHelper;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Default existing behavior: admin = semua true, staff = terbatas sesuai can* lama
        $staffDefaults = [
            // Operasional
            'EmployeeResource' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => false],
            'KasbonResource' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => false],
            'OutletResource' => ['view' => false, 'create' => false, 'edit' => false, 'delete' => false],
            'PayrollResource' => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            'ExpenseResource' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => false],
            // Persediaan
            'IngredientResource' => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            'StockResource' => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            'StockTransferResource' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => false],
            'StockOpnameResource' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => false],
            'SaranReorder' => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            // Produk
            'MenuItemResource' => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            // Penjualan
            'SalesTransactionResource' => ['view' => true, 'create' => true, 'edit' => false, 'delete' => false],
            'Pos' => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            'TutupKasirHarian' => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            // Laporan (semua report hanya butuh view)
            'SalesByOutletReport' => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            'MenuBestSellerReport' => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            'IngredientConsumptionReport' => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            'StockOpnameVarianceReport' => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            'PayrollKasbonReport' => ['view' => false, 'create' => false, 'edit' => false, 'delete' => false], // sensitif
            'MenuMarginReport' => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            'ExpenseReport' => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            // Pengaturan
            'UserResource' => ['view' => false, 'create' => false, 'edit' => false, 'delete' => false],
            'ActivityLogResource' => ['view' => false, 'create' => false, 'edit' => false, 'delete' => false],
        ];

        foreach (['admin', 'staff'] as $role) {
            foreach (RolePermissionHelper::resourceMap() as $key => $label) {
                $defaults = $role === 'admin'
                    ? ['view' => true, 'create' => true, 'edit' => true, 'delete' => true]
                    : ($staffDefaults[$key] ?? ['view' => false, 'create' => false, 'edit' => false, 'delete' => false]);

                RolePermission::updateOrCreate(
                    ['role' => $role, 'resource_key' => $key],
                    [
                        'can_view' => $defaults['view'],
                        'can_create' => $defaults['create'],
                        'can_edit' => $defaults['edit'],
                        'can_delete' => $defaults['delete'],
                    ]
                );
            }
        }

        RolePermissionHelper::clearCache();
    }
}
