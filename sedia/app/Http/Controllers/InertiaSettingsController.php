<?php

namespace App\Http\Controllers;

use App\Models\RolePermission as PermissionModel;
use App\Models\Setting;
use App\Support\Branding;
use App\Support\OutletContext;
use App\Support\RolePermission;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class InertiaSettingsController extends Controller
{
    private function admin(): void { abort_unless(OutletContext::user()?->isAdmin(), 403); }

    public function branding(): Response
    {
        $this->admin();
        return Inertia::render('Settings/Branding', ['branding' => [
            'app_name' => Branding::appName(), 'business_address' => Setting::get('business_address'), 'business_phone' => Setting::get('business_phone'),
            'app_primary_color' => Branding::primaryColor(), 'app_logo_path' => Setting::get('app_logo_path'), 'app_favicon_path' => Setting::get('app_favicon_path'), 'app_design' => Setting::get('app_design', 'lime'),
        ]]);
    }

    public function saveBranding(Request $request): RedirectResponse
    {
        $this->admin();
        $data = $request->validate(['app_name' => ['required', 'string', 'max:60'], 'business_address' => ['nullable', 'string', 'max:255'], 'business_phone' => ['nullable', 'string', 'max:20'], 'app_primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'], 'app_design' => ['required', 'in:lime,midnight,coral,ocean,lavender,forest,monochrome,terracotta,royal,sand'], 'app_logo_path' => ['nullable', 'image', 'max:1024'], 'app_favicon_path' => ['nullable', 'file', 'max:1024', 'mimes:ico,png,svg']]);
        foreach (['app_logo_path', 'app_favicon_path'] as $file) if ($request->hasFile($file)) $data[$file] = $request->file($file)->store('branding', 'public');
        foreach (['app_name', 'business_address', 'business_phone', 'app_primary_color', 'app_design', 'app_logo_path', 'app_favicon_path'] as $key) if (array_key_exists($key, $data)) Setting::set($key, $data[$key]);
        return back()->with('success', 'Branding disimpan.');
    }

    public function permissions(): Response
    {
        $this->admin();
        return Inertia::render('Settings/Permissions', ['resourceMap' => RolePermission::resourceMap(), 'groups' => RolePermission::groupMap(), 'permissions' => $this->loadPermissions('staff')]);
    }

    public function permissionData(string $role): array
    {
        $this->admin(); abort_unless($role === 'staff', 404);
        return ['permissions' => $this->loadPermissions($role)];
    }

    public function savePermissions(Request $request): RedirectResponse
    {
        $this->admin();
        $data = $request->validate(['role' => ['required', 'in:staff'], 'permissions' => ['required', 'array']]);
        DB::transaction(function () use ($data) { foreach (RolePermission::resourceMap() as $key => $label) PermissionModel::updateOrCreate(['role' => $data['role'], 'resource_key' => $key], ['can_view' => (bool) data_get($data, "permissions.$key.view"), 'can_create' => (bool) data_get($data, "permissions.$key.create"), 'can_edit' => (bool) data_get($data, "permissions.$key.edit"), 'can_delete' => (bool) data_get($data, "permissions.$key.delete")]); });
        RolePermission::clearCache($data['role']); return back()->with('success', 'Hak akses disimpan.');
    }

    public function resetPermissions(Request $request): RedirectResponse
    {
        $this->admin(); $role = $request->validate(['role' => ['required', 'in:staff']])['role'];
        PermissionModel::where('role', $role)->delete(); app(RolePermissionSeeder::class)->run(); RolePermission::clearCache($role);
        return back()->with('success', 'Hak akses direset.');
    }

    private function loadPermissions(string $role): array
    {
        $rows = PermissionModel::where('role', $role)->get()->keyBy('resource_key');
        return collect(RolePermission::resourceMap())->mapWithKeys(fn ($label, $key) => [$key => ['label' => $label, 'view' => (bool) ($rows[$key]?->can_view ?? $role === 'admin'), 'create' => (bool) ($rows[$key]?->can_create ?? $role === 'admin'), 'edit' => (bool) ($rows[$key]?->can_edit ?? $role === 'admin'), 'delete' => (bool) ($rows[$key]?->can_delete ?? $role === 'admin')]])->all();
    }
}
