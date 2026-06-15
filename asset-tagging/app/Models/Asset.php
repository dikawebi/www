<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{

use HasFactory;
    protected $fillable = [
        'asset_id', // <--- PASTIKAN BARIS INI ADA DAN TERTULIS DENGAN BENAR
        'category_id',
        'brand_id',
        'name',
        'serial_number',
        'status',
        'pr_number',
        'po_number',
        'location_id',
        'department_id',
        'user_name',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
    ];

   public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function histories() {
    return $this->hasMany(AssetHistory::class)->latest();
}
    //
}
