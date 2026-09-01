<?php

namespace Tests\Feature\Resources\Departments;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentResourceTest extends TestCase
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

    public function test_department_can_be_created_via_model()
    {
        $department = Department::create(['name' => 'IT Department']);

        $this->assertDatabaseHas('departments', ['name' => 'IT Department']);
        $this->assertNotNull($department->id);
    }

    public function test_department_can_be_updated_via_model()
    {
        $department = Department::create(['name' => 'IT Department']);

        $department->update(['name' => 'Updated IT Department']);

        $this->assertDatabaseHas('departments', ['name' => 'Updated IT Department']);
    }

    public function test_department_can_be_deleted_via_model()
    {
        $department = Department::create(['name' => 'IT Department']);

        $department->delete();

        $this->assertDatabaseMissing('departments', ['name' => 'IT Department']);
    }
}
