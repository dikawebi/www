<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Branding
{
    public static function appName(): string
    {
        return Setting::get('app_name', config('app.name', 'Sedia')) ?? 'Sedia';
    }

    public static function appLogoUrl(): ?string
    {
        $path = Setting::get('app_logo_path');
        if ($path && Storage::disk('public')->exists($path)) {
            return '/storage/'.Str::after($path, 'storage/') ?: $path;
        }

        return null;
    }

    public static function faviconUrl(): string
    {
        $path = Setting::get('app_favicon_path');
        if ($path && Storage::disk('public')->exists($path)) {
            return '/storage/'.Str::after($path, 'storage/') ?: $path;
        }

        return '/favicon.png';
    }

    public static function primaryColor(): string
    {
        return Setting::get('app_primary_color', '#f59e0b') ?? '#f59e0b';
    }

    public static function appLogoPath(): ?string
    {
        return Setting::get('app_logo_path');
    }

    public static function businessAddress(): ?string
    {
        return Setting::get('business_address');
    }

    public static function businessPhone(): ?string
    {
        return Setting::get('business_phone');
    }
}
