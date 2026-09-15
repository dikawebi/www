<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Models\Employee;
use App\Models\EmployeeTransaction;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\MenuRecipe;
use App\Models\Outlet;
use App\Models\Payroll;
use App\Models\RolePermission as RolePermissionModel;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\User;
use App\Services\ReportService;
use App\Services\StockService;
use App\Support\RolePermission;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QaRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_management_remains_admin_only_even_if_staff_permission_is_granted(): void
    {
        $outlet = Outlet::create(['name' => 'A', 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'outlet_id' => $outlet->id]);
        RolePermissionModel::updateOrCreate(['role' => 'staff', 'resource_key' => 'UserResource'], ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true]);
        RolePermission::clearCache('staff');

        $this->actingAs($staff)->get('/app/master/users')->assertForbidden();
        $this->actingAs($staff)->post('/app/master/users', [
            'name' => 'Escalated', 'email' => 'escalated@example.com', 'password' => 'password',
            'role' => 'admin', 'is_active' => true,
        ])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'escalated@example.com']);
    }

    public function test_duplicate_stock_items_are_rejected(): void
    {
        $source = Outlet::create(['name' => 'A', 'is_active' => true]);
        $target = Outlet::create(['name' => 'B', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin']);
        $ingredient = Ingredient::create(['name' => 'Tepung', 'unit' => 'kg', 'is_active' => true]);
        $items = [
            ['ingredient_id' => $ingredient->id, 'quantity' => 1],
            ['ingredient_id' => $ingredient->id, 'quantity' => 2],
        ];

        $this->actingAs($admin)->post('/app/stock-transfers', ['source' => 'transfer', 'from_outlet_id' => $source->id, 'to_outlet_id' => $target->id, 'items' => $items])
            ->assertSessionHasErrors('items.0.ingredient_id');
        $this->actingAs($admin)->post('/app/stock-opnames', ['outlet_id' => $source->id, 'opname_date' => now()->toDateString(), 'items' => [
            ['ingredient_id' => $ingredient->id, 'actual_qty' => 1],
            ['ingredient_id' => $ingredient->id, 'actual_qty' => 2],
        ]])->assertSessionHasErrors('items.0.ingredient_id');
    }

    public function test_opname_applies_absolute_count_against_latest_locked_balance(): void
    {
        $outlet = Outlet::create(['name' => 'A', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin']);
        $ingredient = Ingredient::create(['name' => 'Minyak', 'unit' => 'l', 'is_active' => true]);
        $stock = Stock::create(['outlet_id' => $outlet->id, 'ingredient_id' => $ingredient->id, 'quantity' => 5]);
        $opname = StockOpname::create(['outlet_id' => $outlet->id, 'opname_date' => now(), 'performed_by' => $admin->id, 'status' => 'draft']);
        $item = $opname->items()->create(['ingredient_id' => $ingredient->id, 'system_qty' => 5, 'actual_qty' => 3]);
        $stock->update(['quantity' => 4]);

        $this->actingAs($admin)->post("/app/stock-opnames/{$opname->id}/apply")->assertSessionHasNoErrors();

        $this->assertSame(3.0, (float) $stock->fresh()->quantity);
        $this->assertSame(4.0, (float) $item->fresh()->system_qty);
        $this->assertDatabaseHas('stock_movements', ['reference_type' => $opname->getMorphClass(), 'reference_id' => $opname->id, 'quantity' => -1]);
    }

    public function test_checkout_is_idempotent_and_stock_movements_keep_item_reference(): void
    {
        $outlet = Outlet::create(['name' => 'A', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'outlet_id' => $outlet->id]);
        $ingredient = Ingredient::create(['name' => 'Ayam', 'unit' => 'pcs', 'is_active' => true]);
        $menu = MenuItem::create(['name' => 'Ayam', 'price' => 10000, 'is_active' => true]);
        MenuRecipe::create(['menu_item_id' => $menu->id, 'ingredient_id' => $ingredient->id, 'qty_per_unit' => 1]);
        $stock = Stock::create(['outlet_id' => $outlet->id, 'ingredient_id' => $ingredient->id, 'quantity' => 10]);
        $token = (string) Str::uuid();
        $payload = ['checkout_token' => $token, 'outlet_id' => $outlet->id, 'payment_method' => 'cash', 'paid_amount' => 10000, 'items' => [['menu_item_id' => $menu->id, 'quantity' => 1]]];

        $this->actingAs($admin)->post('/app/pos/checkout', $payload)->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/app/pos/checkout', $payload)->assertSessionHasNoErrors();

        $this->assertDatabaseCount('sales_transactions', 1);
        $this->assertDatabaseCount('sales_transaction_items', 1);
        $this->assertSame(9.0, (float) $stock->fresh()->quantity);
        $movement = StockMovement::where('type', StockMovementType::SaleDeduction->value)->firstOrFail();
        $this->assertNotNull($movement->reference_type);
        $this->assertNotNull($movement->reference_id);

        MenuRecipe::where('menu_item_id', $menu->id)->update(['qty_per_unit' => 3]);
        $sale = SalesTransaction::firstOrFail();
        app(StockService::class)->returnSaleItems($sale, [$sale->items()->firstOrFail()->id => 1], $admin->id, 'Recipe changed');
        $this->assertSame(10.0, (float) $stock->fresh()->quantity);
    }

    public function test_payroll_is_draft_only_and_employee_must_match_outlet(): void
    {
        $outletA = Outlet::create(['name' => 'A', 'is_active' => true]);
        $outletB = Outlet::create(['name' => 'B', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = Employee::create(['outlet_id' => $outletA->id, 'name' => 'Ani', 'status' => 'active']);
        EmployeeTransaction::create(['employee_id' => $employee->id, 'outlet_id' => $outletA->id, 'type' => 'kasbon', 'amount' => 100000, 'trans_date' => now(), 'status' => 'approved']);
        $payload = ['outlet_id' => $outletB->id, 'employee_id' => $employee->id, 'pay_date' => now()->toDateString(), 'period_start' => now()->startOfMonth()->toDateString(), 'period_end' => now()->endOfMonth()->toDateString(), 'base_salary' => 500000, 'kasbon_deduction' => 50000, 'status' => 'paid'];

        $this->actingAs($admin)->post('/app/operations/payrolls', $payload)->assertSessionHasErrors('employee_id');
        $payload['outlet_id'] = $outletA->id;
        $this->actingAs($admin)->post('/app/operations/payrolls', $payload)->assertSessionHasNoErrors();
        $payroll = Payroll::firstOrFail();
        $this->assertSame('draft', $payroll->status);

        $this->actingAs($admin)->post("/app/operations/payrolls/{$payroll->id}/pay")->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payrolls', ['id' => $payroll->id, 'status' => 'paid', 'processed_by' => $admin->id]);
    }

    public function test_kasbon_creates_persistent_notifications_for_admin_and_employee_user(): void
    {
        $outlet = Outlet::create(['name' => 'A', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff', 'outlet_id' => $outlet->id]);
        $employee = Employee::create(['outlet_id' => $outlet->id, 'user_id' => $staff->id, 'name' => 'Ani', 'status' => 'active']);

        $this->actingAs($staff)->post('/app/operations/kasbons', ['employee_id' => $employee->id, 'amount' => 50000, 'trans_date' => now()->toDateString()])->assertSessionHasNoErrors();
        $this->assertSame(1, $admin->fresh()->unreadNotifications()->count());

        $kasbon = EmployeeTransaction::firstOrFail();
        $this->actingAs($admin)->post("/app/operations/kasbons/{$kasbon->id}/approve")->assertSessionHasNoErrors();
        $this->assertSame(1, $staff->fresh()->unreadNotifications()->count());
    }

    public function test_discount_is_allocated_to_menu_report_and_partial_refund(): void
    {
        $outlet = Outlet::create(['name' => 'A', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin']);
        $menu = MenuItem::create(['name' => 'Paket', 'price' => 10000, 'is_active' => true]);
        $sale = SalesTransaction::create(['invoice_number' => 'DISC-1', 'outlet_id' => $outlet->id, 'transaction_date' => now(), 'subtotal_amount' => 20000, 'discount_amount' => 2000, 'total_amount' => 18000, 'payment_method' => 'cash', 'status' => 'completed']);
        $item = $sale->items()->create(['menu_item_id' => $menu->id, 'quantity' => 2, 'price' => 10000, 'subtotal' => 20000]);

        $this->actingAs($admin);
        $report = app(ReportService::class)->generate('menu-best-seller', now()->toDateString(), now()->toDateString(), $outlet->id);
        $this->assertSame(18000.0, (float) $report['rows'][0]['total_revenue']);

        $return = app(StockService::class)->returnSaleItems($sale->fresh(), [$item->id => 1], $admin->id, 'Test');
        $this->assertSame(9000.0, (float) $return->total_refund);
    }

    public function test_closing_cash_excludes_change(): void
    {
        $outlet = Outlet::create(['name' => 'A', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin']);
        SalesTransaction::create(['invoice_number' => 'CASH-1', 'outlet_id' => $outlet->id, 'transaction_date' => now(), 'subtotal_amount' => 90000, 'total_amount' => 90000, 'payment_method' => 'cash', 'payments' => [['method' => 'cash', 'amount' => 100000]], 'paid_amount' => 100000, 'change_amount' => 10000, 'status' => 'completed']);

        $this->actingAs($admin)->get('/app/operations/closing?date='.now()->toDateString())
            ->assertInertia(fn (Assert $page) => $page->where('summary.payments.cash', 90000));
    }
}
