<?php

namespace App\Providers;

use App\Models\SalesTransactionItem;
use App\Observers\SalesTransactionItemObserver;
use Illuminate\Support\ServiceProvider;

class ObserverServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        SalesTransactionItem::observe(SalesTransactionItemObserver::class);

        // StockTransferItemObserver dan StockOpnameItemObserver menyusul
        // dengan pola yang sama pas resource Transfer & Opname dibuatkan.
    }
}
