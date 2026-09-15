<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Employee;
use App\Models\EmployeeTransaction;
use App\Models\MenuItem;
use App\Models\MenuRecipe;
use App\Models\Outlet;
use App\Models\Stock;
use App\Models\StockOpname;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InertiaAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_the_inertia_login_page(): void
    {
        $this->withoutVite();

        $this->get('/app/login')
            ->assertOk()
            ->assertSee('data-page');
    }

    public function test_authenticated_user_can_open_the_inertia_dashboard(): void
    {
        $this->withoutVite();
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->get('/app')
            ->assertOk()
            ->assertSee('data-page')
            ->assertSee('Dashboard');
    }

    public function test_guest_is_redirected_to_inertia_login(): void
    {
        $this->get('/app')->assertRedirect('/app/login');
    }

    public function test_staff_cannot_open_admin_settings(): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        $this->actingAs($user)->get('/app/settings/permissions')->assertForbidden();
    }

    public function test_admin_can_open_master_and_stock_pages(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->get('/app/master/menus')->assertOk()->assertSee('data-page');
        $this->actingAs($user)->get('/app/stock')->assertOk()->assertSee('data-page');
    }

    public function test_pos_stock_failure_returns_validation_notification_instead_of_error_page(): void
    {
        $outlet = Outlet::create(['name' => 'Sempu', 'is_active' => true]);
        $user = User::factory()->create(['role' => 'admin', 'outlet_id' => $outlet->id]);
        $ingredient = Ingredient::create(['name' => 'Ayam Spicy', 'unit' => 'pcs', 'is_active' => true]);
        $menu = MenuItem::create(['name' => 'Ayam Spicy', 'category' => 'Makanan', 'price' => 15000, 'is_active' => true]);
        MenuRecipe::create(['menu_item_id' => $menu->id, 'ingredient_id' => $ingredient->id, 'qty_per_unit' => 1]);
        Stock::create(['outlet_id' => $outlet->id, 'ingredient_id' => $ingredient->id, 'quantity' => 0]);

        $this->actingAs($user)->from('/app/pos')->post('/app/pos/checkout', [
            'checkout_token' => (string) \Illuminate\Support\Str::uuid(),
            'outlet_id' => $outlet->id,
            'payment_method' => 'cash',
            'paid_amount' => 15000,
            'items' => [['menu_item_id' => $menu->id, 'quantity' => 1]],
        ])->assertRedirect('/app/pos')->assertSessionHasErrors('checkout');

        $this->assertDatabaseCount('sales_transactions', 0);
    }

    public function test_permission_data_is_loaded_as_plain_json_without_inertia_navigation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->getJson('/app/settings/permissions/staff')
            ->assertOk()
            ->assertJsonStructure(['permissions']);
    }

    public function test_kasbon_approval_is_atomic_and_repeat_action_returns_notification(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet A', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'outlet_id' => $outlet->id]);
        $employee = Employee::create(['outlet_id' => $outlet->id, 'name' => 'Ani', 'status' => 'active']);
        $kasbon = EmployeeTransaction::create(['employee_id' => $employee->id, 'outlet_id' => $outlet->id, 'type' => 'kasbon', 'amount' => 100000, 'trans_date' => now(), 'status' => 'pending']);

        $this->actingAs($admin)->from('/app/operations/kasbons')->post("/app/operations/kasbons/{$kasbon->id}/approve")
            ->assertRedirect('/app/operations/kasbons')
            ->assertSessionHas('success', 'Kasbon disetujui.');
        $this->assertDatabaseHas('employee_transactions', ['id' => $kasbon->id, 'status' => 'approved']);
        $this->assertDatabaseHas('employee_transactions', ['id' => $kasbon->id, 'reviewed_by' => $admin->id]);

        $this->actingAs($admin)->from('/app/operations/kasbons')->post("/app/operations/kasbons/{$kasbon->id}/approve")
            ->assertRedirect('/app/operations/kasbons')
            ->assertSessionHasErrors('kasbon');
    }

    public function test_pos_supports_discount_split_payment_and_notes(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet A', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'outlet_id' => $outlet->id]);
        $menu = MenuItem::create(['name' => 'Paket A', 'price' => 15000, 'is_active' => true]);

        $this->actingAs($admin)->from('/app/pos')->post('/app/pos/checkout', [
            'checkout_token' => (string) \Illuminate\Support\Str::uuid(),
            'outlet_id' => $outlet->id,
            'payment_method' => 'cash',
            'paid_amount' => 14000,
            'payments' => [['method' => 'cash', 'amount' => 4000], ['method' => 'qris', 'amount' => 10000]],
            'discount_amount' => 1000,
            'notes' => 'Bawa pulang',
            'items' => [['menu_item_id' => $menu->id, 'quantity' => 1, 'notes' => 'Tanpa sambal']],
        ])->assertRedirect('/app/pos')->assertSessionHas('receipt_url');

        $this->assertDatabaseHas('sales_transactions', ['subtotal_amount' => 15000, 'discount_amount' => 1000, 'total_amount' => 14000, 'payment_method' => 'split', 'notes' => 'Bawa pulang']);
        $this->assertDatabaseHas('sales_transaction_items', ['notes' => 'Tanpa sambal']);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create(['email' => 'inactive@example.com', 'password' => 'password', 'is_active' => false]);
        $this->post('/app/login', ['email' => 'inactive@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_transfer_ship_and_receive_records_actors_and_balances(): void
    {
        $source = Outlet::create(['name' => 'Source', 'is_active' => true]);
        $destination = Outlet::create(['name' => 'Destination', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'outlet_id' => $source->id]);
        $ingredient = Ingredient::create(['name' => 'Tepung', 'unit' => 'kg', 'is_active' => true]);
        Stock::create(['outlet_id' => $source->id, 'ingredient_id' => $ingredient->id, 'quantity' => 5]);
        $transfer = StockTransfer::create(['source' => 'transfer', 'from_outlet_id' => $source->id, 'to_outlet_id' => $destination->id, 'status' => 'draft', 'created_by' => $admin->id]);
        $transfer->items()->create(['ingredient_id' => $ingredient->id, 'quantity' => 2]);

        $this->actingAs($admin)->from('/app/stock-transfers')->post("/app/stock-transfers/{$transfer->id}/ship")->assertSessionHas('success');
        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id, 'status' => 'sent', 'transferred_by' => $admin->id]);
        $this->assertDatabaseHas('stocks', ['outlet_id' => $source->id, 'ingredient_id' => $ingredient->id, 'quantity' => 3]);

        $this->actingAs($admin)->from('/app/stock-transfers')->post("/app/stock-transfers/{$transfer->id}/receive")->assertSessionHas('success');
        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id, 'status' => 'received', 'received_by' => $admin->id]);
        $this->assertDatabaseHas('stocks', ['outlet_id' => $destination->id, 'ingredient_id' => $ingredient->id, 'quantity' => 2]);
    }

    public function test_stock_opname_apply_records_actor_and_adjusts_balance(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet A', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'outlet_id' => $outlet->id]);
        $ingredient = Ingredient::create(['name' => 'Minyak', 'unit' => 'liter', 'is_active' => true]);
        Stock::create(['outlet_id' => $outlet->id, 'ingredient_id' => $ingredient->id, 'quantity' => 5]);
        $opname = StockOpname::create(['outlet_id' => $outlet->id, 'opname_date' => now(), 'performed_by' => $admin->id, 'status' => 'draft']);
        $opname->items()->create(['ingredient_id' => $ingredient->id, 'system_qty' => 5, 'actual_qty' => 3]);

        $this->actingAs($admin)->from('/app/stock-opnames')->post("/app/stock-opnames/{$opname->id}/apply")->assertSessionHas('success');
        $this->assertDatabaseHas('stock_opnames', ['id' => $opname->id, 'status' => 'applied', 'applied_by' => $admin->id]);
        $this->assertDatabaseHas('stocks', ['outlet_id' => $outlet->id, 'ingredient_id' => $ingredient->id, 'quantity' => 3]);
    }
}
