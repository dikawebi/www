<?php

namespace App\Observers;

use App\Models\MenuItem;
use App\Models\SalesTransactionItem;
use App\Services\StockService;

class SalesTransactionItemObserver
{
    public function __construct(protected StockService $stockService) {}

    public function creating(SalesTransactionItem $item): void
    {
        // Harga & subtotal selalu dari master MenuItem, bukan dari input browser
        // (mencegah tamper via DevTools/Livewire).
        if ($item->menu_item_id) {
            $menu = MenuItem::find($item->menu_item_id);
            if ($menu) {
                $item->price = (float) $menu->price;
                $item->subtotal = (float) $menu->price * (int) $item->quantity;
            }
        }
    }

    public function saving(SalesTransactionItem $item): void
    {
        // Jaga konsistensi subtotal bila quantity berubah
        if ($item->isDirty('quantity') || $item->isDirty('price')) {
            $item->subtotal = (float) $item->price * (int) $item->quantity;
        }
    }

    /**
     * Item baru ditambahkan (baik lewat Repeater di form utama, RelationManager,
     * atau lewat Tinker/API). Dua hal terjadi, keduanya dijamin oleh Eloquent
     * event, bukan oleh reaktivitas Livewire di browser:
     *   1. Stock ingredient dipotong sesuai resep menu (hanya jika transaksi completed).
     *   2. total_amount transaksi dihitung ulang dari total subtotal item.
     */
    public function created(SalesTransactionItem $item): void
    {
        $item->loadMissing('salesTransaction');

        if ($item->salesTransaction?->status === 'completed') {
            $this->stockService->deductForSaleItem($item);
        }

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
     * dikembalikan hanya bila transaksi berstatus completed, dan total_amount dihitung ulang.
     */
    public function deleted(SalesTransactionItem $item): void
    {
        $item->loadMissing('salesTransaction');

        if ($item->salesTransaction?->status === 'completed') {
            $this->stockService->restoreForSaleItem($item);
        }

        // recalc butuh parent masih ada; jika parent sudah soft-delete skip
        if ($item->salesTransaction) {
            $item->salesTransaction->recalculateTotalAmount();
        }
    }
}
