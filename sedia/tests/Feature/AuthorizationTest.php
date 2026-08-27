<?php

namespace Tests\Feature;

use App\Filament\Resources\MenuItemResource;
use App\Filament\Resources\PayrollResource;
use App\Filament\Resources\StockOpnameResource;
use App\Filament\Resources\UserResource;
use App\Models\Employee;
use App\Models\MenuItem;
use App\Models\Outlet;
use App\Models\Payroll;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\StockOpname;
use App\Models\User;
use App\Support\OutletContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_staff_cannot_manage_users(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet A', 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'outlet_id' => $outlet->id]);
        $adminUser = User::factory()->create(['role' => 'admin', 'outlet_id' => $outlet->id]);
        $target = User::factory()->create(['role' => 'staff', 'outlet_id' => $outlet->id]);

        $this->actingAs($staff);
        $this->assertFalse(UserResource::canViewAny());
        $this->assertFalse(UserResource::canCreate());
        $this->assertFalse(UserResource::canEdit($target));
        $this->assertFalse(UserResource::canDelete($target));

        $this->actingAs($adminUser);
        $this->assertTrue(UserResource::canViewAny());
        $this->assertTrue(UserResource::canCreate());
        $this->assertTrue(UserResource::canEdit($target));
        $this->assertTrue(UserResource::canDelete($target));
    }

    public function test_staff_with_null_outlet_sees_nothing(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet A', 'is_active' => true]);
        Employee::create([
            'outlet_id' => $outlet->id,
            'name' => 'Budi',
            'status' => 'active',
        ]);

        $staffNull = User::factory()->create(['role' => 'staff', 'outlet_id' => null]);

        $this->actingAs($staffNull);
        $query = OutletContext::visibleQuery(Employee::query());
        $this->assertEquals(0, $query->count());
    }

    public function test_stock_opname_paid_is_locked_for_staff(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet A', 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'outlet_id' => $outlet->id]);
        $opnamePaid = StockOpname::create([
            'outlet_id' => $outlet->id,
            'opname_date' => now(),
            'performed_by' => $staff->id,
            'status' => 'applied',
        ]);
        $opnameDraft = StockOpname::create([
            'outlet_id' => $outlet->id,
            'opname_date' => now(),
            'performed_by' => $staff->id,
            'status' => 'draft',
        ]);

        $this->actingAs($staff);
        $this->assertFalse(StockOpnameResource::canEdit($opnamePaid));
        $this->assertTrue(StockOpnameResource::canEdit($opnameDraft));
    }

    public function test_payroll_paid_is_locked(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet A', 'is_active' => true]);
        $employee = Employee::create(['outlet_id' => $outlet->id, 'name' => 'Ani', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'outlet_id' => $outlet->id]);

        $payrollPaid = Payroll::create([
            'outlet_id' => $outlet->id,
            'employee_id' => $employee->id,
            'pay_date' => now(),
            'period_start' => now()->subMonth(),
            'period_end' => now(),
            'base_salary' => 1000000,
            'bonus_masuk' => 0,
            'bonus_goreng' => 0,
            'kasbon_deduction' => 0,
            'status' => 'paid',
        ]);

        $this->actingAs($admin);
        $this->assertFalse(PayrollResource::canEdit($payrollPaid));
    }

    public function test_sales_item_price_is_forced_from_menu(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet A', 'is_active' => true]);
        $user = User::factory()->create(['role' => 'admin', 'outlet_id' => $outlet->id]);
        $menu = MenuItem::create(['name' => 'Ayam Goreng', 'price' => 25000, 'is_active' => true]);

        $trx = SalesTransaction::create([
            'invoice_number' => 'INV-TEST-1',
            'outlet_id' => $outlet->id,
            'transaction_date' => now(),
            'total_amount' => 0,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        // Coba kirim price palsu 1000 (harusnya di-override jadi 25000)
        $item = SalesTransactionItem::create([
            'sales_transaction_id' => $trx->id,
            'menu_item_id' => $menu->id,
            'quantity' => 2,
            'price' => 1000,
            'subtotal' => 2000,
        ]);

        $item->refresh();
        $this->assertEquals(25000, (float) $item->price);
        $this->assertEquals(50000, (float) $item->subtotal);
    }

    public function test_menu_item_only_admin_can_manage(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet A', 'is_active' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'outlet_id' => $outlet->id]);
        $admin = User::factory()->create(['role' => 'admin', 'outlet_id' => $outlet->id]);
        $menu = MenuItem::create(['name' => 'Nasi', 'price' => 10000, 'is_active' => true]);

        $this->actingAs($staff);
        $this->assertFalse(MenuItemResource::canCreate());
        $this->assertFalse(MenuItemResource::canEdit($menu));

        $this->actingAs($admin);
        $this->assertTrue(MenuItemResource::canCreate());
        $this->assertTrue(MenuItemResource::canEdit($menu));
    }
}
