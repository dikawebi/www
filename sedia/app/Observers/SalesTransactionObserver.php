<?php

namespace App\Observers;

use App\Models\SalesTransaction;
use App\Services\StockService;

class SalesTransactionObserver
{
    public function __construct(protected StockService $stockService) {}

    public function created(SalesTransaction $transaction): void
    {
        if ($transaction->status === 'completed') {
            $this->stockService->deductForSale($transaction);
        }
    }

    public function updated(SalesTransaction $transaction): void
    {
        // Void: tidak rollback stok otomatis — butuh opname manual
        // Kalau status berubah dari non-completed ke completed (edge case), potong stok
        if ($transaction->wasChanged('status')
            && $transaction->status === 'completed'
            && $transaction->getOriginal('status') !== 'completed'
        ) {
            $this->stockService->deductForSale($transaction);
        }
    }
}
