<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne; // <-- Tambahkan import ini

class Category extends Model
{
    //
    protected $fillable = ['name'];

    public function assetSequence(): HasOne
    {
        return $this->hasOne(AssetSequence::class, 'category_id');
    }
}
