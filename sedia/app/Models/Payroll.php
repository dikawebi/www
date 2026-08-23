<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'outlet_id', 'employee_id', 'pay_date', 'period_start', 'period_end',
        'base_salary', 'bonus_masuk', 'bonus_goreng', 'kasbon_deduction',
        'total_salary', 'status', 'note',
    ];

    protected $casts = [
        'pay_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'base_salary' => 'decimal:2',
        'bonus_masuk' => 'decimal:2',
        'bonus_goreng' => 'decimal:2',
        'kasbon_deduction' => 'decimal:2',
        'total_salary' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Total Gaji selalu dihitung ulang di server, tidak mengandalkan input form,
        // supaya nilainya konsisten walau di-edit lewat mana pun.
        static::saving(function (Payroll $payroll) {
            $payroll->total_salary = (float) $payroll->base_salary
                + (float) $payroll->bonus_masuk
                + (float) $payroll->bonus_goreng
                - (float) $payroll->kasbon_deduction;
        });
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
