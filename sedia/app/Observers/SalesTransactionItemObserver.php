<?php

namespace App\Observers;

use App\Models\SalesTransactionItem;
use App\Services\StockService;

class SalesTransactionItemObserver
{
    public function __construct(protected StockService $stockService) {}

    /**
     * Item baru ditambahkan (baik lewat Repeater di form utama, RelationManager,
     * atau lewat Tinker/API). Dua hal terjadi, keduanya dijamin oleh Eloquent
     * event, bukan oleh reaktivitas Livewire di browser:
     *   1. Stock ingredient dipotong sesuai resep menu (StockService).
     *   2. total_amount transaksi dihitung ulang dari total subtotal item.
     */
    public function created(SalesTransactionItem $item): void
    {
        $this->stockService->deductForSaleItem($item);

        $item->salesTransaction->recalculateTotalAmount();
    }

    /**
     * Catatan: perubahan quantity/price setelah item dibuat TIDAK otomatis
     * menyesuaikan stock movement yang sudah tercatat (supaya tidak dobel-hitung
     * tanpa sengaja). Kalau kasir salah input, alurnya: hapus item lalu buat
     * ulang — itu akan trigger created() + deleted() dengan benar.
     * total_amount tetap di-refresh di sini karena aman dihitung ulang kapan saja.
     */
    public function updated(SalesTransactionItem $item): void
    {
        $item->salesTransaction->recalculateTotalAmount();
    }

    /**
     * Item dihapus (misal kasir salah pencet menu). Stock yang tadi kepotong
     * dikembalikan, dan total_amount dihitung ulang.
     */
    public function deleted(SalesTransactionItem $item): void
    {
        $this->stockService->restoreForSaleItem($item);

        $item->salesTransaction->recalculateTotalAmount();
    }
}
