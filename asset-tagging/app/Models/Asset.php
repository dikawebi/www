<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{

use HasFactory;
    protected $fillable = [
        'name',
        'category_id',
        'location_id',
        'department_id',
        'pr_number',
        'po_number',
        'user_name',
        'status',
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
    //
}
