<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset Cached Roles & Permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Buat Permission (Contoh: untuk akses aset dan brand)
        $permissions = [
            'view_assets', 'create_assets', 'edit_assets', 'delete_assets',
            'view_brands', 'create_brands', 'edit_brands', 'delete_brands'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 3. Buat Role Admin dan assign semua permission
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->givePermissionTo(Permission::all());

        // 4. Buat Role Staff (User biasa)
        $staffRole = Role::firstOrCreate(['name' => 'Staff']);
        $staffRole->givePermissionTo(['view_assets', 'view_brands']);

        // 5. Buat User Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@email.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('administrator'),
            ]
        );
        $admin->assignRole($adminRole);

        // 6. Buat User Staff
        $staff = User::firstOrCreate(
            ['email' => 'staff@email.com'],
            [
                'name' => 'Staff',
                'password' => Hash::make('staff'),
            ]
        );
        $staff->assignRole($staffRole);
    }
}
