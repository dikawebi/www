<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

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
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function histories()
    {
        return $this->hasMany(AssetHistory::class)->latest();
    }

    public function getCurrentLocationAttribute()
    {
        // Mengambil lokasi terakhir dari history jika ada,
        // jika tidak ada, ambil dari data awal (tabel assets)
        $lastHistory = $this->histories()->latest()->first();
        return $lastHistory ? $lastHistory->keLokasi->name : ($this->location ? $this->location->name : 'Gudang');
    }

    public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->with(['category', 'location', 'department']);
}
    //
}
