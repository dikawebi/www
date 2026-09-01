<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_has_fillable_name()
    {
        $category = new Category();
        $this->assertEquals(['name'], $category->getFillable());
    }
}
