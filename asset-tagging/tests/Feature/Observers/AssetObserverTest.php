<?php

namespace Tests\Feature\Observers;

use App\Models\Asset;
use App\Models\Department;
use App\Models\Location;
use App\Models\Category;
use App\Models\Brand;
use App\Models\AssetSequence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AssetObserverTest extends TestCase
{
    use RefreshDatabase;

    private function setupDependencies()
    {
        $department = Department::create(['name' => 'IT']);
        $location = Location::create(['name' => 'Office']);
        $category = Category::create(['name' => 'Electronics']);
        $brand = Brand::create(['name' => 'Dell']);

        return compact('department', 'location', 'category', 'brand');
    }

    public function test_asset_creation_requires_department_for_user()
    {
        $this->setupDependencies();

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'department_id' => null,
        ]);

        Auth::login($user);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('User harus memiliki departemen untuk membuat aset.');

        Asset::create([
            'name' => 'Laptop',
            'serial_number' => 'SN12345',
            'status' => 'In use',
        ]);
    }

    public function test_asset_creation_requires_asset_sequence()
    {
        $deps = $this->setupDependencies();

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'department_id' => $deps['department']->id,
        ]);

        Auth::login($user);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Pengaturan nomor urut (sequence) untuk departemen Anda belum dibuat.');

        Asset::create([
            'name' => 'Laptop',
            'serial_number' => 'SN12345',
            'status' => 'In use',
            'location_id' => $deps['location']->id,
            'category_id' => $deps['category']->id,
            'brand_id' => $deps['brand']->id,
        ]);
    }

    public function test_asset_auto_generates_id_from_sequence()
    {
        $deps = $this->setupDependencies();

        AssetSequence::create([
            'department_id' => $deps['department']->id,
            'prefix' => 'IT',
            'format' => '{prefix}-{year}-{sequence}',
            'next_value' => 1,
            'padding' => 4,
        ]);

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'department_id' => $deps['department']->id,
        ]);

        Auth::login($user);

        $asset = Asset::create([
            'name' => 'Laptop',
            'serial_number' => 'SN12345',
            'status' => 'In use',
            'location_id' => $deps['location']->id,
            'category_id' => $deps['category']->id,
            'brand_id' => $deps['brand']->id,
            'department_id' => $deps['department']->id,
        ]);

        $this->assertStringStartsWith('IT-' . date('Y') . '-0001', $asset->asset_id);
        $this->assertEquals(2, AssetSequence::where('department_id', $deps['department']->id)->first()->next_value);
    }
}
