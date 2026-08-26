<?php

namespace App\Enums;

enum StockMovementType: string
{
    case SaleDeduction = 'sale_deduction';   // otomatis, dari penjualan
    case Purchase = 'purchase';              // belanja dari stockist
    case Expired = 'expired';                // stock dibuang, expired
    case Reject = 'reject';                  // stock dibuang, reject
    case TransferIn = 'transfer_in';         // masuk dari outlet lain
    case TransferOut = 'transfer_out';       // keluar ke outlet lain
    case OpnameAdjustment = 'opname_adjustment'; // koreksi hasil stock opname
    case SaleReturn = 'sale_return';          // retur penjualan (kembalikan stok)

    public function label(): string
    {
        return match ($this) {
            self::SaleDeduction => 'Pemakaian penjualan',
            self::Purchase => 'Pembelian / belanja stockist',
            self::Expired => 'Expired',
            self::Reject => 'Reject',
            self::TransferIn => 'Transfer masuk',
            self::TransferOut => 'Transfer keluar',
            self::OpnameAdjustment => 'Koreksi stock opname',
            self::SaleReturn => 'Retur penjualan',
        };
    }

    // Movement yang menambah stock vs mengurangi stock, dipakai untuk validasi arah quantity
    public function isInbound(): bool
    {
        return in_array($this, [self::Purchase, self::TransferIn, self::SaleReturn]);
    }
}
