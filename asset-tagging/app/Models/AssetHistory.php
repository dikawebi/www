<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
