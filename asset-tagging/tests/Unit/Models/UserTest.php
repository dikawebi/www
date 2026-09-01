<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_belongs_to_department()
    {
        $department = Department::create(['name' => 'IT']);
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'department_id' => $department->id,
        ]);

        $this->assertInstanceOf(Department::class, $user->department);
        $this->assertEquals('IT', $user->department->name);
    }

    public function test_user_has_fillable_attributes()
    {
        $user = new User();
        $this->assertEquals(['name', 'email', 'password', 'department_id'], $user->getFillable());
    }
}
