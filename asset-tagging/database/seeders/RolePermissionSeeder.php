<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Bersihkan cache permission bawaan Spatie agar tidak error
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Daftar semua izin (Permissions) yang dibutuhkan aplikasi Asset Tagging
        $permissions = [
            // Hak Akses Aset Utama
            'view_assets', 'create_assets', 'edit_assets', 'delete_assets', 'scan_qr_assets', 'print_qr_assets',

            // Hak Akses Master Data (Lokasi)
            'view_locations', 'create_locations', 'edit_locations', 'delete_locations',

            // 💡 TAMBAHAN: Hak Akses Kategori
            'view_categories', 'create_categories', 'edit_categories', 'delete_categories',

            // 💡 TAMBAHAN: Hak Akses Departemen
            'view_departments', 'create_departments', 'edit_departments', 'delete_departments',

            // Hak Akses Rahasia (Hanya untuk Admin)
            'view_users', 'create_users', 'edit_users', 'delete_users', 'manage_roles_permissions',
        ];

        // 3. Masukkan semua izin di atas ke dalam tabel database
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 4. Buat Role (Jabatan) dan Bagikan Kunci Izinnya

        // ROLE A: Super Admin (Berikan SEMUA izin tanpa terkecuali)
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->givePermissionTo(Permission::all()); // Ini otomatis mencakup izin baru

        // ROLE B: Manager (Izin menengah: bisa kelola aset, kategori, dan departemen)
        $manager = Role::firstOrCreate(['name' => 'Manager']);
        $manager->givePermissionTo([
            'view_assets', 'create_assets', 'edit_assets', 'print_qr_assets',
            'view_locations', 'create_locations', 'edit_locations',
            'view_categories', 'create_categories', 'edit_categories', // Tambahkan ini
            'view_departments', 'create_departments', 'edit_departments', // Tambahkan ini
            'view_users',
        ]);

        // ROLE C: Staff Lapangan (Izin sangat terbatas: cuma bisa lihat aset dan scan QR)
        $staffLapangan = Role::firstOrCreate(['name' => 'Staff Lapangan']);
        $staffLapangan->givePermissionTo([
            'view_assets',
            'scan_qr_assets',
        ]);

        $this->command->info('Seeder Role dan Permission berhasil dieksekusi! 🎉');
    }
}
