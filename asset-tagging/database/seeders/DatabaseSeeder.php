<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Panggil urut sesuai relasi (Master Data dulu, baru Aset)
        $this->call([
            UserSeeder::class,
            RolePermissionSeeder::class,
            MasterDataSeeder::class,
        ]);
    }
}
