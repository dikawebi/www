<?php

namespace App\Filament\Pages\Reports;

use App\Support\OutletContext;
use Filament\Pages\Page;
use UnitEnum;

abstract class Report extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    public string $startDate;

    public string $endDate;

    public ?int $outletId = null;

    public function mount(): void
    {
        $this->startDate = request()->query('start_date') ?: now()->startOfMonth()->toDateString();
        $this->endDate = request()->query('end_date') ?: now()->toDateString();

        $requestedOutletId = request()->query('outlet_id');
        $this->outletId = filled($requestedOutletId) ? (int) $requestedOutletId : null;

        // Staff tidak boleh intip outlet lain walau utak-atik query string manual.
        $user = OutletContext::user();
        if ($user && ! $user->isAdmin()) {
            $this->outletId = $user->outlet_id;
        }
    }

    protected function periodStart(): string
    {
        return $this->startDate.' 00:00:00';
    }

    protected function periodEnd(): string
    {
        return $this->endDate.' 23:59:59';
    }

    /**
     * @return array<int, string>
     */
    public function outletOptions(): array
    {
        return OutletContext::selectableOutletOptions();
    }

    public function isAdminUser(): bool
    {
        return OutletContext::user()?->isAdmin() ?? false;
    }

    public function formatRupiah(float|int|string|null $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }

    /**
     * Preset rentang tanggal cepat, ditampilkan sebagai tombol shortcut di
     * atas filter. Key => [start, end, label].
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public function quickRanges(): array
    {
        $today = now();

        return [
            'today' => [$today->copy()->toDateString(), $today->copy()->toDateString(), 'Hari Ini'],
            'this_week' => [$today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString(), 'Minggu Ini'],
            'this_month' => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->toDateString(), 'Bulan Ini'],
            'last_month' => [$today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(), 'Bulan Lalu'],
        ];
    }

    public function isActiveQuickRange(string $start, string $end): bool
    {
        return $this->startDate === $start && $this->endDate === $end;
    }
}
