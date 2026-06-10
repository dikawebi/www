<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // 💡 1. Import trait Spatie

class User extends Authenticatable
{
    use Notifiable, HasRoles; // 💡 2. Pasang trait di sini

    protected $fillable = [
        'name',
        'email',
        'password',
    ];
}
