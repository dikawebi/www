<?php

namespace App\Filament\User\Widgets;

use App\Models\Asset;
use Filament\Widgets\ChartWidget;

class DepartmentAssetChart extends ChartWidget
{
    protected ?string $heading = 'Proporsi Status Kondisi Semua Aset';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        // Mengambil data kondisi dari seluruh aset secara global
        $inUse = Asset::where('status', 'In use')->count();
        $broke = Asset::where('status', 'Broke')->count();
        $idle = Asset::where('status', 'Idle')->count();

        if ($inUse === 0 && $broke === 0 && $idle === 0) {
            return [
                'datasets' => [['data' => [1], 'backgroundColor' => ['#f3f4f6']]],
                'labels' => ['Belum Ada Data Aset'],
            ];
        }

        return [
            'datasets' => [
                [
                    'data' => [$inUse, $broke, $idle],
                    'backgroundColor' => ['#22c55e', '#ef4444', '#eab308'],
                ],
            ],
            'labels' => ['Siap Digunakan', 'Rusak', 'Tersedia (Idle)'],
        ];
    }
}
