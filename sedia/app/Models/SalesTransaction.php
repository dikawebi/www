<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SalesTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number', 'outlet_id', 'cashier_id',
        'transaction_date', 'total_amount', 'payment_method', 'status',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'cashier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesTransactionItem::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    /**
     * Sumber kebenaran total_amount: SELALU dihitung ulang dari subtotal item
     * yang benar-benar tersimpan di DB, bukan dari state form di browser.
     * Dipanggil oleh SalesTransactionItemObserver setiap kali item
     * ditambah/diubah/dihapus, jadi nilainya selalu konsisten apapun yang
     * terjadi di reaktivitas form/Livewire.
     */
    public function recalculateTotalAmount(): void
    {
        $this->update([
            'total_amount' => $this->items()->sum('subtotal'),
        ]);
    }
}
