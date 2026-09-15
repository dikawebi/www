<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\Outlet;
use App\Models\Payroll;
use App\Models\RolePermission as RolePermissionModel;
use App\Models\SalesReturn;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Models\User;
use App\Services\ReportService;
use App\Support\RolePermission;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_all_report_pages_render_with_kpi_and_print_button(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet A', 'is_active' => true]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'outlet_id' => $outlet->id,
        ]);

        $reports = ['sales-by-outlet', 'menu-best-seller', 'ingredient-consumption', 'stock-opname-variance', 'payroll-kasbon', 'menu-margin', 'expense'];
        foreach ($reports as $report) {
            $response = $this->actingAs($admin)->get('/app/reports/'.$report);
            $response->assertOk()->assertSee('data-page');
        }
    }

    public function test_staff_report_data_is_scoped_and_cannot_request_another_outlet(): void
    {
        $outletA = Outlet::create(['name' => 'Outlet A', 'is_active' => true]);
        $outletB = Outlet::create(['name' => 'Outlet B', 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'outlet_id' => $outletA->id]);
        SalesTransaction::create(['invoice_number' => 'A-1', 'outlet_id' => $outletA->id, 'transaction_date' => now(), 'total_amount' => 10000, 'payment_method' => 'cash', 'status' => 'completed']);
        SalesTransaction::create(['invoice_number' => 'B-1', 'outlet_id' => $outletB->id, 'transaction_date' => now(), 'total_amount' => 90000, 'payment_method' => 'cash', 'status' => 'completed']);
        Expense::create(['outlet_id' => $outletA->id, 'category' => 'other', 'description' => 'A', 'amount' => 1000, 'expense_date' => now()]);
        Expense::create(['outlet_id' => $outletB->id, 'category' => 'other', 'description' => 'B', 'amount' => 9000, 'expense_date' => now()]);

        $range = '?start_date='.now()->subDay()->toDateString().'&end_date='.now()->addDay()->toDateString();
        $this->actingAs($staff)->get('/app/reports/sales-by-outlet'.$range)
            ->assertInertia(fn (Assert $page) => $page
                ->has('rows', 1)
                ->where('rows.0.outlet_name', 'Outlet A')
                ->where('rows.0.total_omzet', 10000));
        $this->actingAs($staff)->get('/app/reports/expense'.$range)
            ->assertInertia(fn (Assert $page) => $page
                ->has('rows', 1)
                ->where('rows.0.outlet_name', 'Outlet A')
                ->where('rows.0.total_amount', 1000));
        $this->actingAs($staff)->get('/app/reports/sales-by-outlet?outlet_id='.$outletB->id)
            ->assertRedirect()
            ->assertSessionHasErrors('outlet_id');

        $serviceRows = app(ReportService::class)->generate('sales-by-outlet', now()->toDateString(), now()->toDateString(), $outletB->id)['rows'];
        $this->assertSame('Outlet A', $serviceRows[0]['outlet_name']);
    }

    public function test_report_filters_require_ordered_iso_dates_and_an_active_outlet(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet A', 'is_active' => true]);
        $inactive = Outlet::create(['name' => 'Inactive', 'is_active' => false]);
        $admin = User::factory()->create(['role' => 'admin', 'outlet_id' => $outlet->id]);

        $this->actingAs($admin)->get('/app/reports/sales-by-outlet?start_date=16-09-2026&end_date=2026-09-01')
            ->assertSessionHasErrors(['start_date']);
        $this->actingAs($admin)->get('/app/reports/sales-by-outlet?start_date=2026-09-16&end_date=2026-09-01')
            ->assertSessionHasErrors(['start_date', 'end_date']);
        $this->actingAs($admin)->get('/app/reports/sales-by-outlet?start_date=2099-01-01')
            ->assertSessionHasErrors(['start_date']);
        $this->actingAs($admin)->get('/app/reports/sales-by-outlet?end_date=2000-01-01')
            ->assertSessionHasErrors(['start_date', 'end_date']);
        $this->actingAs($admin)->get('/app/reports/sales-by-outlet?outlet_id='.$inactive->id)
            ->assertSessionHasErrors('outlet_id');
        $this->actingAs($admin)->get('/app/reports/sales-by-outlet?outlet_id=999999')
            ->assertSessionHasErrors('outlet_id');
    }

    public function test_report_navigation_only_contains_reports_the_user_can_view(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet A', 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'outlet_id' => $outlet->id]);

        $this->actingAs($staff)->get('/app/reports/sales-by-outlet')
            ->assertInertia(fn (Assert $page) => $page
                ->has('reportNavigation', 6)
                ->where('reportNavigation', fn ($items) => collect($items)->doesntContain('slug', 'payroll-kasbon')));
    }

    public function test_payroll_report_exposes_renderable_detail_rows(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet A', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'outlet_id' => $outlet->id]);
        $employee = Employee::create(['outlet_id' => $outlet->id, 'name' => 'Ani', 'status' => 'active']);
        Payroll::create(['outlet_id' => $outlet->id, 'employee_id' => $employee->id, 'pay_date' => now(), 'period_start' => now()->startOfMonth(), 'period_end' => now()->endOfMonth(), 'base_salary' => 1000000, 'bonus_masuk' => 100000, 'bonus_goreng' => 0, 'kasbon_deduction' => 50000, 'status' => 'paid']);

        $this->actingAs($admin)->get('/app/reports/payroll-kasbon?start_date='.now()->subDay()->toDateString().'&end_date='.now()->addDay()->toDateString())
            ->assertInertia(fn (Assert $page) => $page
                ->has('payrollRows', 1)
                ->has('payrollByPeriodRows', 1)
                ->where('payrollByPeriodRows.0.employee_name', 'Ani')
                ->where('payrollByPeriodRows.0.outlet_name', 'Outlet A')
                ->where('payrollByPeriodRows.0.total_salary', 1050000));
    }

    public function test_dashboard_does_not_disclose_data_for_unauthorized_widgets(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet A', 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'outlet_id' => $outlet->id]);
        $ingredient = Ingredient::create(['name' => 'Secret ingredient', 'unit' => 'kg', 'min_stock' => 10, 'is_active' => true]);
        MenuItem::create(['name' => 'Secret menu', 'price' => 20000, 'is_active' => true]);
        Stock::create(['outlet_id' => $outlet->id, 'ingredient_id' => $ingredient->id, 'quantity' => 1]);
        SalesTransaction::create(['invoice_number' => 'SECRET-1', 'outlet_id' => $outlet->id, 'transaction_date' => now(), 'total_amount' => 75000, 'payment_method' => 'cash', 'status' => 'completed']);
        RolePermissionModel::query()->where('role', 'staff')->whereIn('resource_key', ['SalesTransactionResource', 'MenuBestSellerReport', 'StockResource', 'MenuItemResource', 'IngredientResource'])->update(['can_view' => false]);
        RolePermission::clearCache('staff');

        $this->actingAs($staff)->get('/app')
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.menus', 0)
                ->where('stats.ingredients', 0)
                ->where('stats.transactions', 0)
                ->where('stats.revenue', 0)
                ->has('trend', 0)
                ->has('topMenus', 0)
                ->has('lowStocks', 0));
    }

    public function test_sales_report_includes_refunds_without_current_period_sales(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet A', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'outlet_id' => $outlet->id]);
        $sale = SalesTransaction::create(['invoice_number' => 'OLD-1', 'outlet_id' => $outlet->id, 'transaction_date' => now()->subMonth(), 'total_amount' => 50000, 'payment_method' => 'cash', 'status' => 'completed']);
        SalesReturn::create(['sales_transaction_id' => $sale->id, 'outlet_id' => $outlet->id, 'returned_by' => $admin->id, 'total_refund' => 15000]);

        $this->actingAs($admin)->get('/app/reports/sales-by-outlet?start_date='.now()->toDateString().'&end_date='.now()->toDateString())
            ->assertInertia(fn (Assert $page) => $page
                ->has('rows', 1)
                ->where('rows.0.total_omzet', 0)
                ->where('rows.0.total_retur', 15000)
                ->where('rows.0.net_omzet', -15000));
    }

    public function test_receipt_is_not_accessible_to_staff_from_another_outlet(): void
    {
        $outletA = Outlet::create(['name' => 'Outlet A', 'is_active' => true]);
        $outletB = Outlet::create(['name' => 'Outlet B', 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'outlet_id' => $outletA->id]);
        $sale = SalesTransaction::create(['invoice_number' => 'B-RECEIPT', 'outlet_id' => $outletB->id, 'transaction_date' => now(), 'total_amount' => 50000, 'payment_method' => 'cash', 'status' => 'completed']);

        $this->get(route('receipt.show', $sale))->assertRedirect('/app/login');
        $this->actingAs($staff)->get(route('receipt.show', $sale))->assertForbidden();
    }
}
