<?php

namespace App\Support;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class OutletContext
{
    public const SESSION_KEY = 'selected_outlet_id';

    public static function user(): ?User
    {
        $user = Auth::user();

        return ($user instanceof User) ? $user : null;
    }

    public static function currentOutletId(): ?int
    {
        $user = static::user();

        if (! $user) {
            return null;
        }

        if (! $user->isAdmin()) {
            return $user->outlet_id;
        }

        $outletId = Session::get(static::SESSION_KEY);

        return filled($outletId) ? (int) $outletId : null;
    }

    public static function currentOutlet(): ?Outlet
    {
        $outletId = static::currentOutletId();

        return $outletId ? Outlet::query()->find($outletId) : null;
    }

    public static function selectableOutlets(): Builder
    {
        return Outlet::query()->where('is_active', true)->orderBy('name');
    }

    public static function selectableOutletOptions(): array
    {
        $user = static::user();

        if (! $user) {
            return [];
        }

        if (! $user->isAdmin()) {
            return $user->outlet_id
                ? static::selectableOutlets()->whereKey($user->outlet_id)->pluck('name', 'id')->all()
                : [];
        }

        return static::selectableOutlets()->pluck('name', 'id')->all();
    }

    public static function allOutletOptions(): array
    {
        return Outlet::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all();
    }

    public static function defaultOutletId(): ?int
    {
        $user = static::user();

        if (! $user) {
            return null;
        }

        if ($user->isAdmin()) {
            return static::currentOutletId();
        }

        return $user->outlet_id;
    }

    public static function setCurrentOutletId(?int $outletId): void
    {
        $user = static::user();

        if (! $user || ! $user->isAdmin()) {
            return;
        }

        if ($outletId === null) {
            Session::forget(static::SESSION_KEY);

            return;
        }

        $validOutletId = static::selectableOutlets()->whereKey($outletId)->value('id');

        if ($validOutletId) {
            Session::put(static::SESSION_KEY, (int) $validOutletId);

            return;
        }

        Session::forget(static::SESSION_KEY);
    }

    public static function visibleQuery(Builder $query, string $column = 'outlet_id'): Builder
    {
        $user = static::user();
        $outletId = static::currentOutletId();

        if ($outletId) {
            return $query->where($column, $outletId);
        }

        // Staff tanpa outlet_id yang valid tidak boleh melihat data outlet manapun
        // (sebelumnya `null` = tanpa filter → semua outlet terlihat).
        if ($user && ! $user->isAdmin()) {
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }
}
