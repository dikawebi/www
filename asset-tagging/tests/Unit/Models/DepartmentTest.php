<?php

namespace Tests\Unit\Models;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_can_be_created()
    {
        $department = Department::create(['name' => 'IT']);
        
        $this->assertDatabaseHas('departments', ['name' => 'IT']);
        $this->assertNotNull($department->id);
    }

    public function test_department_has_fillable_name()
    {
        $department = new Department();
        $this->assertEquals(['name'], $department->getFillable());
    }

    public function test_department_has_many_users()
    {
        $department = Department::create(['name' => 'IT']);
        
        // Verify users can reference this department
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'department_id' => $department->id,
        ]);

        $this->assertEquals($department->id, $user->department_id);
    }
}
