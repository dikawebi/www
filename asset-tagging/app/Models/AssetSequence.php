<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetSequence extends Model
{
    protected $fillable = ['department_id', 'prefix', 'format', 'next_value', 'padding'];

 public function department()
{
    return $this->belongsTo(\App\Models\Department::class);
}
}
