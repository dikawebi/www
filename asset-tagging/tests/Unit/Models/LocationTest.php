<?php

namespace Tests\Unit\Models;

use App\Models\Location;
use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_has_assets()
    {
        $location = Location::create(['name' => 'Main Office']);
        $this->assertEquals(0, $location->assets()->count());
    }

    public function test_location_has_fillable_name()
    {
        $location = new Location();
        $this->assertEquals(['name'], $location->getFillable());
    }
}
