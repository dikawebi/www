<?php

namespace App\Exceptions;

use App\Models\Ingredient;
use App\Models\Outlet;
use RuntimeException;

/**
 * Dilempar oleh StockService::recordMovement() kalau suatu pergerakan stock
 * bikin saldo jadi minus. Sengaja EXTENDS RuntimeException (bukan bikin
 * hierarchy baru dari awal) supaya kode lama yang masih catch(RuntimeException)
 * di tempat lain tetap jalan seperti biasa — tapi tempat yang mau nampilkan
 * pesan lebih rapi (misal di halaman Create) bisa catch tipe spesifik ini
 * dan akses data terstrukturnya langsung, tanpa perlu parsing string pesan.
 */
class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly Outlet $outlet,
        public readonly Ingredient $ingredient,
        public readonly float $available,
        public readonly float $required,
        public ?string $menuItemName = null,
    ) {
        parent::__construct(sprintf(
            'Stock %s di outlet %s tidak cukup (saldo: %s, dibutuhkan: %s)',
            $ingredient->name,
            $outlet->name,
            number_format($available, 3),
            number_format($required, 3),
        ));
    }

    public function shortage(): float
    {
        return $this->required - $this->available;
    }

    public function formattedAvailable(): string
    {
        return number_format($this->available, 2).' '.$this->ingredient->unit;
    }

    public function formattedRequired(): string
    {
        return number_format($this->required, 2).' '.$this->ingredient->unit;
    }

    public function formattedShortage(): string
    {
        return number_format($this->shortage(), 2).' '.$this->ingredient->unit;
    }
}