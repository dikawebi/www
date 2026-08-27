<?php

namespace App\Support;

use App\Models\RolePermission as RolePermissionModel;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RolePermission
{
    /**
     * Daftar resource/page key yang dikontrol. Harus sinkron dengan
     * Filament Resources dan Pages yang ada di navigation.
     *
     * @return array<string, string> key => label
     */
    public static function resourceMap(): array
    {
        return [
            // Resources - Operasional
            'EmployeeResource' => 'Karyawan',
            'KasbonResource' => 'Kasbon',
            'OutletResource' => 'Outlet',
            'PayrollResource' => 'Penggajian',
            'ExpenseResource' => 'Kas Kecil',
            // Resources - Persediaan
            'IngredientResource' => 'Bahan Baku',
            'StockResource' => 'Saldo Stok',
            'StockTransferResource' => 'Transfer Stok',
            'StockOpnameResource' => 'Stock Opname',
            // Resources - Produk
            'MenuItemResource' => 'Menu',
            // Resources - Kasir/Penjualan
            'SalesTransactionResource' => 'Transaksi Penjualan',
            // Resources - Pengaturan
            'UserResource' => 'Pengguna',
            'ActivityLogResource' => 'Log Aktivitas',
            // Pages - Penjualan
            'Pos' => 'POS Kasir',
            'TutupKasirHarian' => 'Tutup Kasir Harian',
            // Pages - Persediaan
            'SaranReorder' => 'Saran Reorder',
            // Reports - Laporan
            'SalesByOutletReport' => 'Laporan: Penjualan per Outlet',
            'MenuBestSellerReport' => 'Laporan: Menu Terlaris',
            'IngredientConsumptionReport' => 'Laporan: Pemakaian Bahan Baku',
            'StockOpnameVarianceReport' => 'Laporan: Selisih Stock Opname',
            'PayrollKasbonReport' => 'Laporan: Gaji & Kasbon',
            'MenuMarginReport' => 'Laporan: Laba Kotor per Menu',
            'ExpenseReport' => 'Laporan: Pengeluaran',
        ];
    }

    public static function groupMap(): array
    {
        return [
            'Operasional' => ['EmployeeResource', 'KasbonResource', 'OutletResource', 'PayrollResource', 'ExpenseResource'],
            'Persediaan' => ['IngredientResource', 'StockResource', 'StockTransferResource', 'StockOpnameResource', 'SaranReorder'],
            'Produk' => ['MenuItemResource'],
            'Penjualan' => ['SalesTransactionResource', 'Pos', 'TutupKasirHarian'],
            'Laporan' => ['SalesByOutletReport', 'MenuBestSellerReport', 'IngredientConsumptionReport', 'StockOpnameVarianceReport', 'PayrollKasbonReport', 'MenuMarginReport', 'ExpenseReport'],
            'Pengaturan' => ['UserResource', 'ActivityLogResource'],
        ];
    }

    public static function can(?User $user, string $resourceKey, string $action): bool
    {
        if (! $user) {
            return false;
        }

        // Jika tabel belum ada (sebelum migrate), fallback ke perilaku lama: admin all, staff terbatas
        try {
            $permissions = static::forRole($user->role);
        } catch (\Throwable $e) {
            return $user->isAdmin();
        }

        $key = $resourceKey;
        // Normalisasi: jika full class name, ambil basename
        if (str_contains($key, '\\')) {
            $key = class_basename($key);
        }

        $row = $permissions->get($key);

        if (! $row) {
            // Resource tidak terdaftar di map → default admin all, staff false
            return $user->isAdmin();
        }

        $col = 'can_'.$action; // can_view, can_create, etc.

        return (bool) ($row[$col] ?? false);
    }

    /**
     * @return Collection<string, array{can_view: bool, can_create: bool, can_edit: bool, can_delete: bool}>
     */
    public static function forRole(string $role): Collection
    {
        $array = Cache::remember('role_permissions:'.$role, 3600, function () use ($role) {
            return RolePermissionModel::where('role', $role)
                ->get()
                ->mapWithKeys(fn ($item) => [
                    $item->resource_key => [
                        'can_view' => (bool) $item->can_view,
                        'can_create' => (bool) $item->can_create,
                        'can_edit' => (bool) $item->can_edit,
                        'can_delete' => (bool) $item->can_delete,
                    ],
                ])
                ->all();
        });

        return collect($array);
    }

    public static function clearCache(?string $role = null): void
    {
        if ($role) {
            Cache::forget('role_permissions:'.$role);
        } else {
            Cache::forget('role_permissions:admin');
            Cache::forget('role_permissions:staff');
        }
    }
}
