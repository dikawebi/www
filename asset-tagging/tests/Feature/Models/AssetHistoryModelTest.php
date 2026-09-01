<?php

namespace Tests\Feature\Models;

use App\Models\AssetHistory;
use App\Models\Asset;
use App\Models\AssetSequence;
use App\Models\Department;
use App\Models\Location;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetHistoryModelTest extends TestCase
{
    use RefreshDatabase;

    private function createAsset()
    {
        $department = Department::create(['name' => 'IT']);
        $location = Location::create(['name' => 'Office']);
        $category = Category::create(['name' => 'Electronics']);
        $brand = Brand::create(['name' => 'Dell']);

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

        $asset = Asset::create([
            'asset_id' => 'AST-001',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Laptop',
            'serial_number' => 'SN12345',
            'status' => 'In use',
            'location_id' => $location->id,
            'department_id' => $department->id,
            'user_name' => 'John Doe',
        ]);

        return compact('asset', 'department', 'location');
    }

    public function test_asset_history_can_be_created()
    {
        $data = $this->createAsset();
        
        $history = AssetHistory::create([
            'asset_id' => $data['asset']->id,
            'dari_lokasi' => $data['location']->id,
            'ke_lokasi' => $data['location']->id,
            'dari_departemen' => $data['department']->id,
            'ke_departemen' => $data['department']->id,
            'user_lama' => 'Old User',
            'user_baru' => 'New User',
            'keterangan' => 'Asset transfer',
        ]);

        $this->assertDatabaseHas('asset_histories', [
            'asset_id' => $data['asset']->id,
            'keterangan' => 'Asset transfer',
        ]);
    }

    public function test_asset_history_belongs_to_asset()
    {
        $data = $this->createAsset();
        
        $history = AssetHistory::create([
            'asset_id' => $data['asset']->id,
            'dari_lokasi' => $data['location']->id,
            'ke_lokasi' => $data['location']->id,
            'dari_departemen' => $data['department']->id,
            'ke_departemen' => $data['department']->id,
            'user_lama' => 'Old User',
            'user_baru' => 'New User',
            'keterangan' => 'Test',
        ]);

        $this->assertInstanceOf(Asset::class, $history->asset);
        $this->assertEquals($data['asset']->id, $history->asset->id);
    }
}
