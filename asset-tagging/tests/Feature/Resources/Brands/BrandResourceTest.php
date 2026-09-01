<?php

namespace Tests\Feature\Resources\Brands;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'department_id' => null,
        ]);
        $this->actingAs($user);
    }

    public function test_brand_can_be_created_via_model()
    {
        $brand = Brand::create(['name' => 'Dell']);

        $this->assertDatabaseHas('brands', ['name' => 'Dell']);
        $this->assertNotNull($brand->id);
    }

    public function test_brand_can_be_updated_via_model()
    {
        $brand = Brand::create(['name' => 'Dell']);

        $brand->update(['name' => 'Dell Computers']);

        $this->assertDatabaseHas('brands', ['name' => 'Dell Computers']);
    }

    public function test_brand_requires_unique_name()
    {
        Brand::create(['name' => 'Dell']);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        Brand::create(['name' => 'Dell']);
    }
}
