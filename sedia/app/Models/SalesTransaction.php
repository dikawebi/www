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
        'invoice_number', 'checkout_token', 'created_by', 'outlet_id', 'cashier_id',
        'transaction_date', 'subtotal_amount', 'discount_amount', 'total_amount', 'payment_method', 'payments', 'paid_amount', 'change_amount', 'notes', 'status',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'total_amount' => 'decimal:2',
        'subtotal_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'payments' => 'array',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    public function getPaymentsArray(): array
    {
        if (is_array($this->payments) && ! empty($this->payments)) {
            return $this->payments;
        }

        return [['method' => $this->payment_method ?? 'cash', 'amount' => (float) $this->total_amount]];
    }

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

    public function returns(): HasMany
    {
        return $this->hasMany(SalesReturn::class);
    }

    /**
     * Sumber kebenaran total_amount: SELALU dihitung ulang dari subtotal item
     * yang benar-benar tersimpan di DB, bukan dari state form di browser.
     * Dipanggil oleh SalesTransactionItemObserver setiap kali item
     * ditambah/diubah/dihapus, jadi nilainya selalu konsisten apapun yang
     * terjadi di state form browser.
     */
    public function recalculateTotalAmount(): void
    {
        $subtotal = (float) $this->items()->sum('subtotal');
        $this->update(['subtotal_amount' => $subtotal, 'total_amount' => max(0, $subtotal - (float) $this->discount_amount)]);
    }
}
