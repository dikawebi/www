<?php

namespace App\Filament\Widgets;

use App\Models\SalesTransaction;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class RevenueOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        $query = SalesTransaction::query()->where('status', 'completed');

        if ($user && ! $user->isAdmin() && $user->outlet_id) {
            $query->where('outlet_id', $user->outlet_id);
        }

        $today = (clone $query)->whereDate('transaction_date', today())->sum('total_amount');
        $month = (clone $query)->whereMonth('transaction_date', now()->month)->whereYear('transaction_date', now()->year)->sum('total_amount');
        $count = (clone $query)->count();

        return [
            Stat::make('Omzet Hari Ini', 'Rp '.number_format($today, 0, ',', '.')),
            Stat::make('Omzet Bulan Ini', 'Rp '.number_format($month, 0, ',', '.')),
            Stat::make('Transaksi Selesai', number_format($count)),
        ];
    }
}
