<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role
 * @property int|null $outlet_id
 */
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'outlet_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Ini yang dicek Filament tiap kali user coba masuk panel (setelah
     * login berhasil). Tanpa method ini, panel dianggap TERTUTUP buat
     * semua orang di Filament versi kamu — persis gejala 403 kemarin.
     * Admin dan staff (kasir) sama-sama boleh akses, karena staff juga
     * butuh masuk buat modul Penjualan/POS.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'staff'], true);
    }

    /**
     * Outlet ID yang boleh diakses user ini.
     * Admin: null (tidak dibatasi). Staff: outlet_id mereka.
     */
    public function accessibleOutletId(): ?int
    {
        return $this->isAdmin() ? null : $this->outlet_id;
    }
}
