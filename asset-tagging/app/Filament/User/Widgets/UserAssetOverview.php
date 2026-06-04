<?php

namespace App\Filament\User\Widgets;

use App\Models\Asset;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserAssetOverview extends BaseWidget
{
    protected ?string $pollingInterval = '15s';
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Menghitung seluruh aset global di database
        $totalAset = Asset::count();
        $asetDigunakan = Asset::where('status', 'In use')->count();
        $asetRusak = Asset::where('status', 'Broke')->count();

        return [
            Stat::make('Total Semua Aset', $totalAset . ' Unit')
                ->description('Semua aset yang tercatat di sistem')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),

            Stat::make('Aset Aktif Digunakan', $asetDigunakan . ' Unit')
                ->description('Sedang digunakan operasional')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Aset Rusak', $asetRusak . ' Unit')
                ->description('Butuh tindakan/perbaikan segera')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($asetRusak > 0 ? 'danger' : 'gray'),
        ];
    }
}
