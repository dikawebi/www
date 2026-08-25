<?php

namespace App\Filament\Widgets;

use App\Models\SalesTransaction;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SalesTrendChartWidget extends ChartWidget
{
    protected ?string $heading = 'Tren Penjualan 7 Hari';

    protected function getData(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        $query = SalesTransaction::query()->where('status', 'completed');

        if ($user && ! $user->isAdmin() && $user->outlet_id) {
            $query->where('outlet_id', $user->outlet_id);
        }

        $labels = [];
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $labels[] = now()->subDays($i)->format('d M');
            $data[] = (clone $query)->whereDate('transaction_date', $date)->sum('total_amount');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Omzet',
                    'data' => $data,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
