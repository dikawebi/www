<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AssetHistory extends Model
{
    //
    protected $fillable = [
    'asset_id', 'dari_lokasi', 'ke_lokasi', 'dari_departemen',
    'ke_departemen', 'user_lama', 'user_baru', 'keterangan'
];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    // Tambahkan ini di app/Models/AssetHistory.php
public function location()
{
    return $this->belongsTo(Location::class, 'ke_lokasi'); // 'ke_lokasi' adalah foreign key di tabel asset_histories
}

public function dariLokasi(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'dari_lokasi');
    }

    public function keLokasi(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'ke_lokasi');
    }

    public function departemenTujuan(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'ke_departemen');
    }
}
