<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
//use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Panel;
//use Filament\Models\Contracts\FilamentUser;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     *
     *
     */

    public function canAccessPanel(Panel $panel): bool
    {
        // Panel admin hanya boleh dimasuki oleh user ber-role 'admin'
        if ($panel->getId() === 'admin') {
            return $this->role === 'admin';
        }

        // Panel user boleh dimasuki oleh semua user (admin ataupun staff biasa)
        if ($panel->getId() === 'user') {
            return true;
        }

        return false;
    }
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
