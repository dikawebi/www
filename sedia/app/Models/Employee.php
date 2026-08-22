<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'outlet_id', 'user_id', 'name', 'phone', 'position',
        'base_salary', 'join_date', 'status',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'join_date' => 'date',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(EmployeeTransaction::class);
    }

    public function salesTransactions(): HasMany
    {
        return $this->hasMany(SalesTransaction::class, 'cashier_id');
    }

    // Saldo kasbon berjalan: total kasbon dikurangi total gaji yang sudah dibayarkan
    public function outstandingKasbon(): float
    {
        $kasbon = $this->transactions()->where('type', 'kasbon')->where('status', 'approved')->sum('amount');
        $paid = $this->transactions()->whereIn('type', ['gaji'])->where('status', 'approved')->sum('amount');

        return (float) $kasbon - (float) $paid;
    }
}
