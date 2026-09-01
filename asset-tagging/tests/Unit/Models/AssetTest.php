<?php

namespace Tests\Unit\Models;

use App\Models\Asset;
use App\Models\AssetSequence;
use App\Models\Department;
use App\Models\Location;
use App\Models\Brand;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetTest extends TestCase
{
    use RefreshDatabase;

    private function createAssetDependencies()
    {
        $department = Department::create(['name' => 'IT']);
        $location = Location::create(['name' => 'Office']);
        $brand = Brand::create(['name' => 'Dell']);
        $category = Category::create(['name' => 'Electronics']);

        AssetSequence::create([
            'department_id' => $department->id,
            'prefix' => 'AST',
            'format' => '{prefix}-{year}-{sequence}',
            'next_value' => 1,
            'padding' => 4,
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'department_id' => $department->id,
        ]);

        $this->actingAs($user);

        return compact('department', 'location', 'brand', 'category', 'user');
    }

    public function test_asset_belongs_to_department()
    {
        $deps = $this->createAssetDependencies();

        $asset = Asset::create([
            'asset_id' => 'AST-001',
            'category_id' => $deps['category']->id,
            'brand_id' => $deps['brand']->id,
            'name' => 'Laptop',
            'serial_number' => 'SN12345',
            'status' => 'In use',
            'location_id' => $deps['location']->id,
            'department_id' => $deps['department']->id,
            'user_name' => 'John Doe',
        ]);

        $this->assertInstanceOf(Department::class, $asset->department);
        $this->assertEquals('IT', $asset->department->name);
    }

    public function test_asset_has_histories()
    {
        $deps = $this->createAssetDependencies();

        $asset = Asset::create([
            'asset_id' => 'AST-001',
            'category_id' => $deps['category']->id,
            'brand_id' => $deps['brand']->id,
            'name' => 'Laptop',
            'serial_number' => 'SN12345',
            'status' => 'In use',
            'location_id' => $deps['location']->id,
            'department_id' => $deps['department']->id,
            'user_name' => 'John Doe',
        ]);

        $this->assertCount(0, $asset->histories);
    }

    public function test_asset_has_fillable_attributes()
    {
        $asset = new Asset();
        $fillables = $asset->getFillable();

        $this->assertContains('name', $fillables);
        $this->assertContains('asset_id', $fillables);
        $this->assertContains('department_id', $fillables);
    }
}
