<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'type', 'amount', 'trans_date', 'status', 'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'trans_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
