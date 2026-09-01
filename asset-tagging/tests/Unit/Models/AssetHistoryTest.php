<?php

namespace Tests\Unit\Models;

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

class AssetHistoryTest extends TestCase
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

        return $asset;
    }

    public function test_asset_history_belongs_to_asset()
    {
        $asset = $this->createAsset();
        
        $history = AssetHistory::create([
            'asset_id' => $asset->id,
            'dari_lokasi' => $asset->location_id,
            'ke_lokasi' => $asset->location_id,
            'dari_departemen' => $asset->department_id,
            'ke_departemen' => $asset->department_id,
            'user_lama' => 'Old User',
            'user_baru' => 'New User',
            'keterangan' => 'Asset moved',
        ]);

        $this->assertInstanceOf(Asset::class, $history->asset);
        $this->assertStringStartsWith('AST-' . date('Y'), $history->asset->asset_id);
    }

    public function test_asset_history_ke_lokasi_relationship()
    {
        $asset = $this->createAsset();
        $location = $asset->location;
        
        $history = AssetHistory::create([
            'asset_id' => $asset->id,
            'dari_lokasi' => $location->id,
            'ke_lokasi' => $location->id,
            'dari_departemen' => $asset->department_id,
            'ke_departemen' => $asset->department_id,
            'user_lama' => 'Old User',
            'user_baru' => 'New User',
            'keterangan' => 'Asset moved',
        ]);

        $this->assertInstanceOf(Location::class, $history->keLokasi);
        $this->assertEquals('Office', $history->keLokasi->name);
    }

    public function test_asset_history_departemen_tujuan_relationship()
    {
        $asset = $this->createAsset();
        $department = $asset->department;
        
        $history = AssetHistory::create([
            'asset_id' => $asset->id,
            'dari_lokasi' => $asset->location_id,
            'ke_lokasi' => $asset->location_id,
            'dari_departemen' => $department->id,
            'ke_departemen' => $department->id,
            'user_lama' => 'Old User',
            'user_baru' => 'New User',
            'keterangan' => 'Asset moved',
        ]);

        $this->assertInstanceOf(Department::class, $history->departemenTujuan);
        $this->assertEquals('IT', $history->departemenTujuan->name);
    }
}
