<?php

namespace App\Filament\Pages;

use App\Models\RolePermission as RolePermissionModel;
use App\Support\OutletContext;
use App\Support\RolePermission;
use BackedEnum;
use Database\Seeders\RolePermissionSeeder;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class ManageRolePermissions extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Kelola Hak Akses';

    protected static ?string $title = 'Kelola Hak Akses';

    protected string $view = 'filament.pages.manage-role-permissions';

    public string $selectedRole = 'staff';

    /** @var array<string, array{view:bool, create:bool, edit:bool, delete:bool}> */
    public array $permissions = [];

    public static function canAccess(): bool
    {
        return OutletContext::user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->loadPermissions();
    }

    public function updatedSelectedRole(): void
    {
        $this->loadPermissions();
    }

    public function toggleGroup(string $group, string $action, bool $value): void
    {
        $keys = RolePermission::groupMap()[$group] ?? [];
        foreach ($keys as $key) {
            $this->permissions[$key][$action] = $value;
        }
    }

    public function toggleAll(bool $value): void
    {
        foreach ($this->permissions as $key => $perms) {
            foreach (['view', 'create', 'edit', 'delete'] as $act) {
                $this->permissions[$key][$act] = $value;
            }
        }
    }

    public function loadPermissions(): void
    {
        $role = $this->selectedRole;
        $rows = RolePermissionModel::where('role', $role)->get()->keyBy('resource_key');

        $this->permissions = [];
        foreach (RolePermission::resourceMap() as $key => $label) {
            $row = $rows->get($key);
            $this->permissions[$key] = [
                'view' => (bool) ($row?->can_view ?? ($role === 'admin')),
                'create' => (bool) ($row?->can_create ?? ($role === 'admin')),
                'edit' => (bool) ($row?->can_edit ?? ($role === 'admin')),
                'delete' => (bool) ($row?->can_delete ?? ($role === 'admin')),
            ];
        }
    }

    public function save(): void
    {
        $role = $this->selectedRole;

        DB::transaction(function () use ($role) {
            foreach ($this->permissions as $resourceKey => $perms) {
                RolePermissionModel::updateOrCreate(
                    ['role' => $role, 'resource_key' => $resourceKey],
                    [
                        'can_view' => (bool) ($perms['view'] ?? false),
                        'can_create' => (bool) ($perms['create'] ?? false),
                        'can_edit' => (bool) ($perms['edit'] ?? false),
                        'can_delete' => (bool) ($perms['delete'] ?? false),
                    ]
                );
            }
        });

        RolePermission::clearCache($role);

        Notification::make()->title('Hak akses disimpan')->body('Role '.ucfirst($role).' diperbarui.')->success()->send();
    }

    public function resetToDefault(): void
    {
        // Hapus lalu seed ulang untuk role terpilih
        RolePermissionModel::where('role', $this->selectedRole)->delete();
        app(RolePermissionSeeder::class)->run();
        $this->loadPermissions();
        Notification::make()->title('Direset ke default')->success()->send();
    }
}
