<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelConsumption extends Model
{
    // Gunakan row_id sebagai Primary Key
    protected $primaryKey = 'row_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'row_id', // Tambahkan ini
        'JournalNumber',
        'TransactionDate',
        'WBPDTNumber',
        'WBPHMFirst',
        'WBPHMEnd',
        'InventoryQuantity',
        'CostAmount'
    ];
}
