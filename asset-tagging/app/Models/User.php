<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser; // 💡 Import ini
use Filament\Panel; // 💡 Import ini
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser // 💡 Tambahkan implements
{
    use Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'department_id', // 💡 Pastikan ini ada
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // 💡 TAMBAHKAN FUNGSI INI
    public function canAccessPanel(Panel $panel): bool
    {
        // Untuk testing, izinkan semua user login.
        // Nanti bisa diganti dengan: return $this->hasRole('Super Admin');
        return true;
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\Department::class);
    }
}
