<?php

namespace Tests\Feature;

use App\Filament\Pages\Reports\IngredientConsumptionReport;
use App\Filament\Pages\Reports\MenuBestSellerReport;
use App\Filament\Pages\Reports\MenuMarginReport;
use App\Filament\Pages\Reports\PayrollKasbonReport;
use App\Filament\Pages\Reports\SalesByOutletReport;
use App\Filament\Pages\Reports\StockOpnameVarianceReport;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

        $reports = [
            SalesByOutletReport::class,
            MenuBestSellerReport::class,
            IngredientConsumptionReport::class,
            StockOpnameVarianceReport::class,
            PayrollKasbonReport::class,
            MenuMarginReport::class,
        ];

        foreach ($reports as $report) {
            Livewire::actingAs($admin)
                ->test($report)
                ->assertSuccessful()
                ->assertSee('Cetak');
        }
    }
}
