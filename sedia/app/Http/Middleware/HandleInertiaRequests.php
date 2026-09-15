<?php

namespace App\Http\Middleware;

use App\Support\OutletContext;
use App\Support\RolePermission;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $request->user()?->only(['id', 'name', 'email', 'role', 'outlet_id']),
                'outlets' => fn () => OutletContext::selectableOutletOptions(),
                'currentOutlet' => fn () => OutletContext::currentOutlet()?->only(['id', 'name']),
                'permissions' => fn () => $request->user()
                    ? RolePermission::forRole($request->user()->role)
                    : [],
            ],
            'design' => fn () => Setting::get('app_design', 'lime'),
            'notifications' => fn () => $request->user() ? [
                'unread_count' => $request->user()->unreadNotifications()->count(),
                'items' => $request->user()->notifications()->latest()->limit(8)->get()->map(fn ($notification) => [
                    'id' => $notification->id,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at?->diffForHumans(),
                ]),
            ] : ['unread_count' => 0, 'items' => []],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'receipt_url' => fn () => $request->session()->get('receipt_url'),
            ],
        ];
    }
}
