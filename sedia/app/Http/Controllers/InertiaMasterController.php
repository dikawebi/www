<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\MenuRecipe;
use App\Models\Outlet;
use App\Models\User;
use App\Support\OutletContext;
use App\Support\RolePermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InertiaMasterController extends Controller
{
    public function recipes(int $menu): Response
    {
        $definition = $this->definition('menus'); $this->authorize($definition, 'view');
        $menuItem = MenuItem::query()->with('recipes.ingredient')->findOrFail($menu);
        return Inertia::render('Master/Recipes', ['menu' => $menuItem->only(['id', 'name']), 'recipes' => $menuItem->recipes->map(fn ($recipe) => ['id' => $recipe->id, 'ingredient_id' => $recipe->ingredient_id, 'ingredient_name' => $recipe->ingredient?->name, 'unit' => $recipe->ingredient?->unit, 'qty_per_unit' => $recipe->qty_per_unit]), 'ingredients' => Ingredient::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit'])]);
    }

    public function storeRecipe(Request $request, int $menu): RedirectResponse
    {
        $this->authorize($this->definition('menus'), 'edit');
        MenuItem::query()->findOrFail($menu);
        $data = $request->validate(['ingredient_id' => ['required', 'integer', 'exists:ingredients,id'], 'qty_per_unit' => ['required', 'numeric', 'gt:0']]);
        MenuRecipe::updateOrCreate(['menu_item_id' => $menu, 'ingredient_id' => $data['ingredient_id']], ['qty_per_unit' => $data['qty_per_unit']]); return back()->with('success', 'Resep diperbarui.');
    }

    public function destroyRecipe(int $menu, int $recipe): RedirectResponse
    {
        $this->authorize($this->definition('menus'), 'edit'); MenuRecipe::query()->where('menu_item_id', $menu)->findOrFail($recipe)->delete(); return back()->with('success', 'Bahan resep dihapus.');
    }
    private const RESOURCES = [
        'outlets' => ['model' => Outlet::class, 'key' => 'OutletResource', 'title' => 'Outlet', 'fields' => ['name', 'address', 'phone', 'receipt_header', 'receipt_footer', 'is_active']],
        'ingredients' => ['model' => Ingredient::class, 'key' => 'IngredientResource', 'title' => 'Bahan Baku', 'fields' => ['name', 'unit', 'cost_per_unit', 'min_stock', 'is_active']],
        'menus' => ['model' => MenuItem::class, 'key' => 'MenuItemResource', 'title' => 'Menu', 'fields' => ['name', 'category', 'barcode', 'price', 'is_active']],
        'employees' => ['model' => Employee::class, 'key' => 'EmployeeResource', 'title' => 'Karyawan', 'fields' => ['outlet_id', 'user_id', 'name', 'phone', 'position', 'base_salary', 'join_date', 'status']],
        'expenses' => ['model' => Expense::class, 'key' => 'ExpenseResource', 'title' => 'Pengeluaran', 'fields' => ['outlet_id', 'category', 'description', 'amount', 'expense_date', 'note']],
        'users' => ['model' => User::class, 'key' => 'UserResource', 'title' => 'Pengguna', 'fields' => ['name', 'email', 'password', 'role', 'outlet_id', 'is_active']],
    ];

    public function index(Request $request, string $resource): Response
    {
        $definition = $this->definition($resource);
        $this->authorize($definition, 'view');
        $class = $definition['model'];
        $query = $class::query();

        if (in_array($resource, ['employees', 'expenses'], true)) {
            $query = OutletContext::visibleQuery($query);
        }
        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(function ($builder) use ($term, $definition) {
                $searchable = array_values(array_intersect($definition['fields'], ['name', 'address', 'phone', 'receipt_header', 'receipt_footer', 'unit', 'category', 'barcode', 'position', 'status', 'description', 'note', 'email', 'role']));
                foreach ($searchable as $index => $field) {
                    $index === 0 ? $builder->where($field, 'like', "%{$term}%") : $builder->orWhere($field, 'like', "%{$term}%");
                }
            });
        }

        $rows = $query->latest()->get()->map(function ($model) use ($definition) {
            return collect($model->only($definition['fields']))
                ->put('id', $model->id)
                ->put('outlet_name', method_exists($model, 'outlet') ? $model->outlet?->name : null)
                ->except('password')
                ->all();
        })->values();

        return Inertia::render('Master/Index', [
            'resource' => ['slug' => $resource, 'title' => $definition['title'], 'fields' => $definition['fields']],
            'rows' => $rows,
            'search' => $request->query('q', ''),
            'outlets' => OutletContext::selectableOutletOptions(),
            'users' => $resource === 'employees' ? User::query()->where('is_active', true)->when(! OutletContext::user()->isAdmin(), fn ($query) => $query->where('role', 'staff')->where('outlet_id', OutletContext::user()->outlet_id))->orderBy('name')->get(['id', 'name', 'email', 'outlet_id']) : [],
        ]);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $definition = $this->definition($resource);
        $this->authorize($definition, 'create');
        $data = $this->validated($request, $resource);

        if ($resource === 'expenses') {
            $data['created_by'] = $request->user()->id;
        }

        if (in_array($resource, ['employees', 'expenses'], true)) {
            $this->ensureOutletAccess($request, $data['outlet_id'] ?? null);
        }

        $class = $definition['model'];
        $class::create($data);

        return back()->with('success', $definition['title'].' berhasil dibuat.');
    }

    public function update(Request $request, string $resource, int $id): RedirectResponse
    {
        $definition = $this->definition($resource);
        $this->authorize($definition, 'edit');
        $class = $definition['model'];
        $query = $class::query();
        if (in_array($resource, ['employees', 'expenses'], true)) {
            $query = OutletContext::visibleQuery($query);
        }
        $model = $query->findOrFail($id);
        $data = $this->validated($request, $resource, $model);

        if ($resource === 'users' && $model->is($request->user()) && (($data['role'] ?? $model->role) !== 'admin' || ! ($data['is_active'] ?? $model->is_active))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['user' => 'Admin tidak dapat menurunkan role atau menonaktifkan akunnya sendiri.']);
        }

        if (in_array($resource, ['employees', 'expenses'], true)) {
            $this->ensureOutletAccess($request, $data['outlet_id'] ?? $model->outlet_id);
        }

        $model->update($data);

        return back()->with('success', $definition['title'].' berhasil diperbarui.');
    }

    public function destroy(Request $request, string $resource, int $id): RedirectResponse
    {
        $definition = $this->definition($resource);
        $this->authorize($definition, 'delete');
        $class = $definition['model'];
        $query = $class::query();
        if (in_array($resource, ['employees', 'expenses'], true)) {
            $query = OutletContext::visibleQuery($query);
        }
        $model = $query->findOrFail($id);
        if ($resource === 'users' && $model->is($request->user())) {
            throw \Illuminate\Validation\ValidationException::withMessages(['user' => 'Akun yang sedang digunakan tidak dapat dinonaktifkan.']);
        }

        if (in_array($resource, ['outlets', 'ingredients', 'menus', 'users'], true)) {
            $model->update(['is_active' => false]);
        } elseif ($resource === 'employees') {
            $model->update(['status' => 'inactive']);
        } else {
            $model->delete();
        }

        return back()->with('success', $definition['title'].' berhasil dihapus.');
    }

    public function resetPassword(Request $request, int $id): RedirectResponse
    {
        $this->authorize($this->definition('users'), 'edit');
        $data = $request->validate(['password' => ['required', 'string', 'min:8', 'confirmed']]);
        User::query()->findOrFail($id)->update(['password' => $data['password']]);
        return back()->with('success', 'Password pengguna berhasil direset.');
    }

    private function definition(string $resource): array
    {
        abort_unless(isset(self::RESOURCES[$resource]), 404);

        return self::RESOURCES[$resource];
    }

    private function authorize(array $definition, string $action): void
    {
        $user = OutletContext::user();
        abort_unless($user && ($definition['key'] !== 'UserResource' || $user->isAdmin()), 403);
        abort_unless(RolePermission::can($user, $definition['key'], $action), 403);
    }

    private function validated(Request $request, string $resource, mixed $model = null): array
    {
        $rules = match ($resource) {
            'outlets' => ['name' => ['required', 'string', 'max:255'], 'address' => ['nullable', 'string'], 'phone' => ['nullable', 'string', 'max:50'], 'receipt_header' => ['nullable', 'string'], 'receipt_footer' => ['nullable', 'string'], 'is_active' => ['boolean']],
            'ingredients' => ['name' => ['required', 'string', 'max:255'], 'unit' => ['required', 'string', 'max:30'], 'cost_per_unit' => ['nullable', 'numeric', 'min:0'], 'min_stock' => ['nullable', 'numeric', 'min:0'], 'is_active' => ['boolean']],
            'menus' => ['name' => ['required', 'string', 'max:255'], 'category' => ['nullable', 'string', 'max:100'], 'barcode' => ['nullable', 'string', 'max:100', Rule::unique('menu_items', 'barcode')->ignore($model?->id)], 'price' => ['required', 'numeric', 'min:0'], 'is_active' => ['boolean']],
            'employees' => ['outlet_id' => ['required', 'integer', 'exists:outlets,id'], 'user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('employees', 'user_id')->ignore($model?->id)], 'name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:50'], 'position' => ['nullable', 'string', 'max:100'], 'base_salary' => ['nullable', 'numeric', 'min:0'], 'join_date' => ['nullable', 'date'], 'status' => ['required', Rule::in(['active', 'inactive'])]],
            'expenses' => ['outlet_id' => ['required', 'integer', 'exists:outlets,id'], 'category' => ['required', 'string', 'max:100'], 'description' => ['required', 'string'], 'amount' => ['required', 'numeric', 'gt:0'], 'expense_date' => ['required', 'date'], 'note' => ['nullable', 'string']],
            'users' => ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($model?->id)], 'password' => [$model ? 'nullable' : 'required', 'string', 'min:8'], 'role' => ['required', Rule::in(['admin', 'staff'])], 'outlet_id' => ['nullable', 'integer', 'exists:outlets,id'], 'is_active' => ['boolean']],
        };

        $data = $request->validate($rules);
        if ($resource === 'users' && $data['role'] === 'staff') {
            validator($data, ['outlet_id' => ['required', 'integer']])->validate();
        }
        if ($resource === 'users' && blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        if ($resource === 'employees' && ! empty($data['user_id'])) {
            $linkedUser = User::query()->findOrFail($data['user_id']);
            if ($linkedUser->role === 'staff' && (int) $linkedUser->outlet_id !== (int) $data['outlet_id']) {
                validator([], ['user_id' => [fn ($attribute, $value, $fail) => $fail('Outlet pengguna dan karyawan harus sama.')]])->validate();
            }
        }

        return array_intersect_key($data, array_flip(self::RESOURCES[$resource]['fields']));
    }

    private function ensureOutletAccess(Request $request, mixed $outletId): void
    {
        if (! $request->user()->isAdmin() && (int) $outletId !== (int) $request->user()->outlet_id) {
            abort(403);
        }
    }
}
