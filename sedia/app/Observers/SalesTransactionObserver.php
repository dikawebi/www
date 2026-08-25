<?php

namespace App\Observers;

use App\Models\SalesTransaction;
use App\Services\StockService;

class SalesTransactionObserver
{
    public function __construct(protected StockService $stockService) {}

    public function created(SalesTransaction $transaction): void
    {
        // Stock dipotong per-item oleh SalesTransactionItemObserver::created,
        // bukan di sini, untuk menghindari double-deduction dan agar transaksi
        // yang dibuat sebagai 'void' tidak memotong stok sama sekali.
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
