<?php

namespace Tests\Unit\Models;

use App\Models\AssetSequence;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetSequenceTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_asset_sequence_has_fillable_attributes()
    {
        $sequence = new AssetSequence();
        $fillables = $sequence->getFillable();

        $this->assertContains('department_id', $fillables);
        $this->assertContains('prefix', $fillables);
        $this->assertContains('format', $fillables);
        $this->assertContains('next_value', $fillables);
        $this->assertContains('padding', $fillables);
    }
}
