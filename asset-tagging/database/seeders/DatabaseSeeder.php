<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Location;
use App\Models\Department;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Akun Admin Sampel untuk Login Filament
        User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@email.com',
            'password' => Hash::make('administrator'), // password untuk login
        ]);

        // 2. Isi Data Sampel Kategori
        $categories = ['Laptop', 'Printer', 'Monitor', 'Network Device'];
        foreach ($categories as $category) {
            Category::create(['name' => $category]);
        }

        // 3. Isi Data Sampel Lokasi
        $locations = ['Ruang Server', 'Lantai 1 - Kantor Utama', 'Lantai 2 - Ruang Kerja', 'Gudang A'];
        foreach ($locations as $location) {
            Location::create(['name' => $location]);
        }

        // 4. Isi Data Sampel Departemen
        $departments = ['Information Technology (IT)', 'Human Resources (HR)', 'Finance & Accounting', 'Marketing & Sales'];
        foreach ($departments as $department) {
            Department::create(['name' => $department]);
        }

        // 5. Buat 50 Data Aset Sampel secara Acak menggunakan Factory
        Asset::factory()->count(50)->create();
    }
}
