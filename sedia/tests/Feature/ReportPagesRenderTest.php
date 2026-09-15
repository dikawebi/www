<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_report_pages_render_with_kpi_and_print_button(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet A', 'is_active' => true]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'outlet_id' => $outlet->id,
        ]);

        $reports = ['sales-by-outlet', 'menu-best-seller', 'ingredient-consumption', 'stock-opname-variance', 'payroll-kasbon', 'menu-margin'];
        foreach ($reports as $report) {
            $response = $this->actingAs($admin)->get('/app/reports/'.$report);
            $response->assertOk()->assertSee('data-page');
        }
    }
}
