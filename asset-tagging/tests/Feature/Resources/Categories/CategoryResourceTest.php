<?php

namespace Tests\Feature\Resources\Categories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
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

    public function test_category_can_be_created_via_model()
    {
        $category = Category::create(['name' => 'Electronics']);

        $this->assertDatabaseHas('categories', ['name' => 'Electronics']);
        $this->assertNotNull($category->id);
    }

    public function test_category_can_be_updated_via_model()
    {
        $category = Category::create(['name' => 'Electronics']);

        $category->update(['name' => 'Electronics & Gadgets']);

        $this->assertDatabaseHas('categories', ['name' => 'Electronics & Gadgets']);
    }

    public function test_category_requires_unique_name()
    {
        Category::create(['name' => 'Electronics']);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        Category::create(['name' => 'Electronics']);
    }
}
