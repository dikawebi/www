<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property int|null $from_outlet_id
 * @property int $to_outlet_id
 * @property string $source
 * @property string $status
 * @property int|null $created_by
 */
class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_outlet_id', 'to_outlet_id', 'source', 'status',
        'created_by', 'received_by', 'transferred_at', 'received_at', 'note',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function fromOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'from_outlet_id');
    }

    public function toOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'to_outlet_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }
}
