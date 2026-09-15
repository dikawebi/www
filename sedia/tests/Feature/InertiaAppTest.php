<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Employee;
use App\Models\EmployeeTransaction;
use App\Models\MenuItem;
use App\Models\MenuRecipe;
use App\Models\Outlet;
use App\Models\Stock;
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

        $this->actingAs($admin)->from('/app/operations/kasbons')->post("/app/operations/kasbons/{$kasbon->id}/approve")
            ->assertRedirect('/app/operations/kasbons')
            ->assertSessionHasErrors('kasbon');
    }
}
