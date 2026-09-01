<?php

namespace Tests\Feature\Models;

use App\Models\Asset;
use App\Models\AssetSequence;
use App\Models\Department;
use App\Models\Location;
use App\Models\Brand;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetModelTest extends TestCase
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

    public function test_asset_can_be_created_with_all_fields()
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
            'pr_number' => 'PR-001',
            'po_number' => 'PO-001',
        ]);

        $this->assertDatabaseHas('assets', [
            'name' => 'Laptop',
            'status' => 'In use',
        ]);
    }

    public function test_asset_has_correct_status_badge()
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

        $this->assertEquals('In use', $asset->status);
    }

    public function test_asset_status_values()
    {
        $deps = $this->createAssetDependencies();
        $statuses = ['In use', 'Idle', 'Broke'];

        foreach ($statuses as $i => $status) {
            $asset = Asset::create([
                'asset_id' => 'AST-' . $i,
                'category_id' => $deps['category']->id,
                'brand_id' => $deps['brand']->id,
                'name' => 'Laptop',
                'serial_number' => 'SN-' . $i,
                'status' => $status,
                'location_id' => $deps['location']->id,
                'department_id' => $deps['department']->id,
                'user_name' => 'John Doe',
            ]);

            $this->assertEquals($status, $asset->status);
        }
    }
}
