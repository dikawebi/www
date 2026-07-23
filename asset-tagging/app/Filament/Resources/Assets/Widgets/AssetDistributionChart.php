<?php

namespace App\Filament\Resources\Assets\Widgets;

use App\Models\Asset;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AssetDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Distribusi Aset';

    // Default filter adalah 'category'
    public ?string $filter = 'category';

    protected function getFilters(): ?array
    {
        return [
            'category'   => 'Berdasarkan Kategori',
            'department' => 'Berdasarkan Departemen',
            'location'   => 'Berdasarkan Lokasi',
        ];
    }

    protected function getData(): array
    {
        // Tentukan tabel dan kolom berdasarkan filter yang dipilih
        $mapping = [
            'category'   => ['table' => 'categories', 'column' => 'category_id'],
            'department' => ['table' => 'departments', 'column' => 'department_id'],
            'location'   => ['table' => 'locations', 'column' => 'location_id'],
        ];

        $active = $mapping[$this->filter];

        // Query dinamis
        $data = Asset::query()
            ->join($active['table'], "assets.{$active['column']}", '=', "{$active['table']}.id")
            ->select("{$active['table']}.name", DB::raw('count(assets.id) as count'))
            ->groupBy("{$active['table']}.name")
            ->pluck('count', 'name');

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Aset',
                    'data' => $data->values(),
                    'backgroundColor' => [
                        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'
                    ],
                ],
            ],
            'labels' => $data->keys(),
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Bar chart lebih enak dibaca jika pilihan filternya banyak
    }

    protected static bool $isLazy = true;
}
