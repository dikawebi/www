<?php

namespace Tests\Feature\Models;

use App\Models\User;
use App\Models\Department;
use App\Models\Asset;
use App\Models\Location;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created_with_department()
    {
        $department = Department::create(['name' => 'IT']);
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'department_id' => $department->id,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'department_id' => $department->id,
        ]);
    }

    public function test_user_can_be_updated_with_department()
    {
        $department1 = Department::create(['name' => 'IT']);
        $department2 = Department::create(['name' => 'HR']);
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'department_id' => $department1->id,
        ]);

        $user->update(['department_id' => $department2->id]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'department_id' => $department2->id,
        ]);
    }

    public function test_user_without_department()
    {
        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'department_id' => null,
        ]);

        $this->assertNull($user->department);
    }
}
