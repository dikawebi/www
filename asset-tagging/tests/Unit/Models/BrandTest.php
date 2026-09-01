<?php

namespace Tests\Unit\Models;

use App\Models\Brand;
use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_has_assets()
    {
        $brand = Brand::create(['name' => 'Dell']);
        $this->assertEquals(0, $brand->assets()->count());
    }

    public function test_brand_has_fillable_name()
    {
        $brand = new Brand();
        $this->assertEquals(['name'], $brand->getFillable());
    }
}
