<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'brands' => ['Apple', 'Asus', 'Dell', 'Lenovo', 'HP'],
            'categories' => ['Laptop', 'Monitor', 'Printer', 'Server', 'Peripherals'],
            'departments' => ['IT', 'HRD', 'Finance', 'Marketing', 'Operations'],
            'locations' => ['Gudang Pusat', 'Kantor Jakarta', 'Kantor Surabaya', 'Warehouse B']
        ];

        foreach ($data['brands'] as $name) Brand::firstOrCreate(['name' => $name]);
        foreach ($data['categories'] as $name) Category::firstOrCreate(['name' => $name]);
        foreach ($data['departments'] as $name) Department::firstOrCreate(['name' => $name]);
        foreach ($data['locations'] as $name) Location::firstOrCreate(['name' => $name]);
    }
}
