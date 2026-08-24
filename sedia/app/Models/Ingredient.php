<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 */
class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'unit', 'cost_per_unit', 'min_stock', 'is_active'];

    protected $casts = [
        'cost_per_unit' => 'decimal:2',
        'min_stock' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function menuRecipes(): HasMany
    {
        return $this->hasMany(MenuRecipe::class);
    }

    public function stockTransferItems(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function stockOpnameItems(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }
}
