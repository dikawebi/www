<?php

namespace App\Filament\Resources\Assets\Widgets;

use App\Models\Asset; // Pastikan model Asset Anda benar
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AssetStatsOverview extends BaseWidget
{
    protected function getStats(): array
{
    return [
        Stat::make('In Use', Asset::where('status', 'In use')->count())
            ->description('Barang sedang dipakai')
            ->color('success'),

        Stat::make('Idle', Asset::where('status', 'Idle')->count())
            ->description('Barang tersedia / standby')
            ->color('info'),

        Stat::make('Repair', Asset::where('status', 'Repair')->count())
            ->description('Sedang dalam perbaikan')
            ->color('warning'),

        Stat::make('Broke', Asset::where('status', 'Broke')->count())
            ->description('Kondisi rusak')
            ->color('danger'),
            
        Stat::make('Lost', Asset::where('status', 'Lost')->count())
            ->description('Aset hilang')
            ->color('gray'),
    ];
}
}