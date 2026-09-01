<?php

namespace Tests\Feature\Models;

use App\Models\AssetSequence;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetSequenceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_sequence_can_be_created()
    {
        $department = Department::create(['name' => 'IT']);
        $sequence = AssetSequence::create([
            'department_id' => $department->id,
            'prefix' => 'IT',
            'format' => '{prefix}-{year}-{sequence}',
            'next_value' => 1,
            'padding' => 4,
        ]);

        $this->assertDatabaseHas('asset_sequences', [
            'department_id' => $department->id,
            'prefix' => 'IT',
        ]);
    }

    public function test_asset_sequence_belongs_to_department()
    {
        $department = Department::create(['name' => 'IT']);
        $sequence = AssetSequence::create([
            'department_id' => $department->id,
            'prefix' => 'IT',
            'format' => '{prefix}-{year}-{sequence}',
            'next_value' => 1,
            'padding' => 4,
        ]);

        $this->assertInstanceOf(Department::class, $sequence->department);
        $this->assertEquals('IT', $sequence->department->name);
    }

    public function test_asset_sequence_can_increment_next_value()
    {
        $department = Department::create(['name' => 'IT']);
        $sequence = AssetSequence::create([
            'department_id' => $department->id,
            'prefix' => 'IT',
            'format' => '{prefix}-{year}-{sequence}',
            'next_value' => 100,
            'padding' => 4,
        ]);

        $sequence->increment('next_value');

        $this->assertEquals(101, $sequence->fresh()->next_value);
    }
}
