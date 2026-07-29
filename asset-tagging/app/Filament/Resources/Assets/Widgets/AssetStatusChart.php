<?php

namespace App\Filament\Resources\Assets\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Asset;

class AssetStatusChart extends ChartWidget
{
    protected ?string $heading = 'Assets Status Chart';

 protected function getData(): array
{
    return [
        'datasets' => [
            [
                'label' => 'Status Aset',
                'data' => [
                    Asset::where('status', 'In use')->count(),
                    Asset::where('status', 'Idle')->count(),
                    Asset::where('status', 'Repair')->count(),
                    Asset::where('status', 'Broke')->count(),
                    Asset::where('status', 'Lost')->count(),
                ],
                'backgroundColor' => ['#22c55e', '#f59e0b', '#ef4444', '#6b7280', '#9ca3af'],
            ],
        ],
        'labels' => ['In Use', 'Idle', 'Repair', 'Broke', 'Lost'],
    ];
}

protected function getType(): string
{
    return 'doughnut'; // Tampilan bulat persentase
}
}
