<?php

namespace Tests\Feature\Resources\Locations;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationResourceTest extends TestCase
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

    public function test_location_can_be_created_via_model()
    {
        $location = Location::create(['name' => 'Main Office']);

        $this->assertDatabaseHas('locations', ['name' => 'Main Office']);
        $this->assertNotNull($location->id);
    }

    public function test_location_can_be_updated_via_model()
    {
        $location = Location::create(['name' => 'Main Office']);

        $location->update(['name' => 'Updated Main Office']);

        $this->assertDatabaseHas('locations', ['name' => 'Updated Main Office']);
    }

    public function test_location_requires_unique_name()
    {
        Location::create(['name' => 'Main Office']);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        Location::create(['name' => 'Main Office']);
    }
}
