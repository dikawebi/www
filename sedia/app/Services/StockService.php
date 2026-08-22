<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Ingredient;
use App\Models\Outlet;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockOpnameItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Exceptions\InsufficientStockException;
use RuntimeException;

class StockService
{
    /**
     * Catat 1 pergerakan stock dan update saldo stocks di outlet terkait.
     * $quantity harus signed: positif = stock masuk, negatif = stock keluar.
     * Semua pemanggilan lain (deduct, transfer, opname) memakai method ini di dalamnya.
     */
    public function recordMovement(
        Outlet $outlet,
        Ingredient $ingredient,
        StockMovementType $type,
        float $quantity,
        ?Model $reference = null,
        ?int $createdBy = null,
        ?string $note = null,
    ): StockMovement {
        return DB::transaction(function () use ($outlet, $ingredient, $type, $quantity, $reference, $createdBy, $note) {
            $stock = Stock::firstOrCreate(
                ['outlet_id' => $outlet->id, 'ingredient_id' => $ingredient->id],
                ['quantity' => 0],
            );

            $newBalance = (float) $stock->quantity + (float) $quantity;

            if ($newBalance < 0) {
                throw new InsufficientStockException(
                    outlet: $outlet,
                    ingredient: $ingredient,
                    available: (float) $stock->quantity,
                    required: abs((float) $quantity),
                );
            }

            $stock->update(['quantity' => $newBalance]);

            return StockMovement::create([
                'outlet_id' => $outlet->id,
                'ingredient_id' => $ingredient->id,
                'type' => $type->value,
                'quantity' => (float) $quantity,
                'balance_after' => $newBalance,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'created_by' => $createdBy,
                'note' => $note,
            ]);
        });
    }

    /**
     * Dipanggil setelah SalesTransaction + items disimpan.
     * Untuk tiap item yang terjual, lookup resep (menu_recipes) lalu potong stock ingredient sesuai qty_per_unit x qty terjual.
     */
    public function deductForSale(SalesTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction->load('items.menuItem.recipes.ingredient');

            foreach ($transaction->items as $item) {
                foreach ($item->menuItem->recipes as $recipe) {
                    $totalUsed = (float) $recipe->qty_per_unit * $item->quantity;

                    $this->recordMovement(
                        outlet: $transaction->outlet,
                        ingredient: $recipe->ingredient,
                        type: \App\Enums\StockMovementType::SaleDeduction,
                        quantity: -$totalUsed,
                        reference: $transaction,
                        note: "Terjual: {$item->menuItem->name} x{$item->quantity}",
                    );
                }
            }
        });
    }

    /**
     * Kirim transfer antar outlet.
     * Hanya potong stok outlet asal dan ubah status jadi sent.
     */
    public function shipTransfer(StockTransfer $transfer, ?int $createdBy = null): void
    {
        DB::transaction(function () use ($transfer, $createdBy) {
            $transfer->load('items.ingredient', 'fromOutlet', 'toOutlet');

            if ($transfer->source === 'purchase') {
                throw new RuntimeException('Transfer source purchase tidak bisa dikirim dari outlet asal.');
            }

            if (!$transfer->fromOutlet) {
                throw new RuntimeException('Outlet asal belum diisi.');
            }

            foreach ($transfer->items as $item) {
                $this->recordMovement(
                    outlet: $transfer->fromOutlet,
                    ingredient: $item->ingredient,
                    type: \App\Enums\StockMovementType::TransferOut,
                    quantity: -$item->quantity,
                    reference: $transfer,
                    createdBy: $createdBy ?? $transfer->created_by,
                    note: "Transfer ke {$transfer->toOutlet->name}",
                );
            }

            $transfer->update([
                'status' => 'sent',
                'transferred_at' => $transfer->transferred_at ?? now(),
            ]);
        });
    }

    /**
     * Terima transfer.
     * Tambah stok outlet tujuan dan ubah status jadi received.
     */
    public function receiveTransfer(StockTransfer $transfer, ?int $createdBy = null): void
    {
        DB::transaction(function () use ($transfer, $createdBy) {
            $transfer->load('items.ingredient', 'fromOutlet', 'toOutlet');

            if (!in_array($transfer->status, ['draft', 'sent'], true)) {
                throw new RuntimeException('Transfer sudah diproses.');
            }

            if ($transfer->source === 'transfer' && $transfer->status !== 'sent') {
                throw new RuntimeException('Transfer antar outlet harus dikirim dulu sebelum diterima.');
            }

            foreach ($transfer->items as $item) {
                $this->recordMovement(
                    outlet: $transfer->toOutlet,
                    ingredient: $item->ingredient,
                    type: \App\Enums\StockMovementType::TransferIn,
                    quantity: $item->quantity,
                    reference: $transfer,
                    createdBy: $createdBy ?? $transfer->created_by,
                    note: $transfer->fromOutlet
                        ? "Transfer dari {$transfer->fromOutlet->name}"
                        : 'Belanja dari stockist',
                );
            }

            $transfer->update([
                'status' => 'received',
                'received_by' => $createdBy ?? $transfer->received_by,
                'received_at' => now(),
            ]);
        });
    }

    /**
     * Proses stock_transfers + stock_transfer_items menjadi 2 sisi pergerakan:
     * transfer_out di outlet asal, transfer_in di outlet tujuan.
     * Dipakai kalau workflow perlu langsung selesai dalam 1 langkah.
     */
    public function applyTransfer(StockTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            $transfer->load('items.ingredient', 'fromOutlet', 'toOutlet');

            foreach ($transfer->items as $item) {
                if ($transfer->fromOutlet) {
                    $this->recordMovement(
                        outlet: $transfer->fromOutlet,
                        ingredient: $item->ingredient,
                        type: \App\Enums\StockMovementType::TransferOut,
                        quantity: -$item->quantity,
                        reference: $transfer,
                        createdBy: $transfer->created_by,
                        note: "Transfer ke {$transfer->toOutlet->name}",
                    );
                }

                $this->recordMovement(
                    outlet: $transfer->toOutlet,
                    ingredient: $item->ingredient,
                    type: \App\Enums\StockMovementType::TransferIn,
                    quantity: $item->quantity,
                    reference: $transfer,
                    createdBy: $transfer->created_by,
                    note: $transfer->fromOutlet
                        ? "Transfer dari {$transfer->fromOutlet->name}"
                        : 'Belanja dari stockist',
                );
            }

            $transfer->update(['status' => 'received']);
        });
    }

    /**
     * Catat pembuangan stock diluar pemakaian resep (expired / reject).
     */
    public function writeOff(Outlet $outlet, Ingredient $ingredient, float $quantity, string $reason, ?int $createdBy = null, ?string $note = null): StockMovement
    {
        $type = $reason === 'expired' ? StockMovementType::Expired : StockMovementType::Reject;

        return $this->recordMovement($outlet, $ingredient, $type, -abs($quantity), createdBy: $createdBy, note: $note);
    }

    // ------------------------------------------------------------------
    // Method per-item di bawah ini dipanggil oleh Observer, dipicu tiap
    // 1 baris item (SalesTransactionItem/StockTransferItem/StockOpnameItem)
    // disimpan. Ini yang membuat auto-deduct/auto-transfer bisa jalan tanpa
    // perlu tombol "proses" terpisah di Filament.
    // ------------------------------------------------------------------

    /**
     * Dipanggil oleh SalesTransactionItemObserver::created().
     * Potong stock ingredient sesuai resep menu, sebesar qty_per_unit x quantity item ini SAJA
     * (bukan seluruh transaksi), supaya aman dipanggil per-item tanpa reprocessing.
     */
    public function deductForSaleItem(SalesTransactionItem $item): void
    {
        DB::transaction(function () use ($item) {
            $item->loadMissing('menuItem.recipes.ingredient', 'salesTransaction.outlet');

            foreach ($item->menuItem->recipes as $recipe) {
                try {
                    $this->recordMovement(
                        outlet: $item->salesTransaction->outlet,
                        ingredient: $recipe->ingredient,
                        type: StockMovementType::SaleDeduction,
                        quantity: -((float) $recipe->qty_per_unit * $item->quantity),
                        reference: $item,
                        note: "Terjual: {$item->menuItem->name} x{$item->quantity}",
                    );
                } catch (InsufficientStockException $exception) {
                    // recordMovement() tidak tahu ini dipicu dari penjualan menu apa
                    // (dia juga dipakai oleh transfer & opname yang tidak ada konteks
                    // "menu"). Di sinilah konteksnya ada, jadi kita tempelkan sebelum
                    // exception-nya diteruskan ke atas.
                    $exception->menuItemName = "{$item->menuItem->name} x{$item->quantity}";

                    throw $exception;
                }
            }
        });
    }

    /**
     * Dipanggil oleh SalesTransactionItemObserver::deleted().
     * Kebalikan dari deductForSaleItem — dipakai kalau item penjualan dihapus/dibatalkan
     * (misal salah input), supaya stock yang tadi kepotong balik lagi.
     */
    public function restoreForSaleItem(SalesTransactionItem $item): void
    {
        DB::transaction(function () use ($item) {
            $item->loadMissing('menuItem.recipes.ingredient', 'salesTransaction.outlet');

            foreach ($item->menuItem->recipes as $recipe) {
                $this->recordMovement(
                    outlet: $item->salesTransaction->outlet,
                    ingredient: $recipe->ingredient,
                    type: StockMovementType::SaleDeduction,
                    quantity: (float) $recipe->qty_per_unit * $item->quantity,
                    reference: $item,
                    note: "Batal/hapus item: {$item->menuItem->name} x{$item->quantity}",
                );
            }
        });
    }

    /**
     * Dipanggil oleh StockTransferItemObserver::created().
     * Kalau from_outlet_id ada -> catat transfer_out di outlet asal + transfer_in di outlet tujuan.
     * Kalau from_outlet_id kosong (source = belanja stockist) -> cuma transfer_in di outlet tujuan.
     */
    public function applyTransferItem(StockTransferItem $item): void
    {
        DB::transaction(function () use ($item) {
            $item->loadMissing('ingredient', 'stockTransfer.fromOutlet', 'stockTransfer.toOutlet');
            $transfer = $item->stockTransfer;

            if ($transfer->fromOutlet) {
                $this->recordMovement(
                    outlet: $transfer->fromOutlet,
                    ingredient: $item->ingredient,
                    type: StockMovementType::TransferOut,
                    quantity: -$item->quantity,
                    reference: $item,
                    createdBy: $transfer->created_by,
                    note: "Transfer ke {$transfer->toOutlet->name}",
                );
            }

            $this->recordMovement(
                outlet: $transfer->toOutlet,
                ingredient: $item->ingredient,
                type: StockMovementType::TransferIn,
                quantity: $item->quantity,
                reference: $item,
                createdBy: $transfer->created_by,
                note: $transfer->fromOutlet
                    ? "Transfer dari {$transfer->fromOutlet->name}"
                    : 'Belanja dari stockist',
            );
        });
    }

    /**
     * Dipanggil oleh StockTransferItemObserver::deleted(). Kebalikan dari applyTransferItem.
     */
    public function reverseTransferItem(StockTransferItem $item): void
    {
        DB::transaction(function () use ($item) {
            $item->loadMissing('ingredient', 'stockTransfer.fromOutlet', 'stockTransfer.toOutlet');
            $transfer = $item->stockTransfer;

            $this->recordMovement(
                outlet: $transfer->toOutlet,
                ingredient: $item->ingredient,
                type: StockMovementType::TransferIn,
                quantity: -$item->quantity,
                reference: $item,
                createdBy: $transfer->created_by,
                note: 'Koreksi: transfer item dihapus',
            );

            if ($transfer->fromOutlet) {
                $this->recordMovement(
                    outlet: $transfer->fromOutlet,
                    ingredient: $item->ingredient,
                    type: StockMovementType::TransferOut,
                    quantity: $item->quantity,
                    reference: $item,
                    createdBy: $transfer->created_by,
                    note: 'Koreksi: transfer item dihapus',
                );
            }
        });
    }

    /**
     * Dipanggil oleh StockOpnameItemObserver::created().
     * Selisih (actual_qty - system_qty) langsung dicatat sebagai stock_movement type opname_adjustment.
     * Catatan: kalau item opname di-EDIT setelah dibuat, selisih baru TIDAK otomatis ter-apply lagi
     * (observer cuma listen created) — sengaja dibuat begitu supaya tidak double-adjust tanpa sengaja.
     */
    public function applyOpnameItem(StockOpnameItem $item): void
    {
        $difference = (float) $item->actual_qty - (float) $item->system_qty;

        if (abs($difference) < 0.0001) {
            return; // tidak ada selisih, tidak perlu movement
        }

        $item->loadMissing('ingredient', 'stockOpname.outlet');

        $this->recordMovement(
            outlet: $item->stockOpname->outlet,
            ingredient: $item->ingredient,
            type: StockMovementType::OpnameAdjustment,
            quantity: $difference,
            reference: $item,
            createdBy: $item->stockOpname->performed_by,
            note: "Stock opname {$item->stockOpname->opname_date->format('d M Y')}: sistem {$item->system_qty}, aktual {$item->actual_qty}",
        );
    }
}