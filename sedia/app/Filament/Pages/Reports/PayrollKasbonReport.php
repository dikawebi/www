<?php

namespace App\Filament\Pages\Reports;

use App\Models\Employee;
use App\Models\Payroll;
use App\Support\OutletContext;
use BackedEnum;
use Illuminate\Support\Collection;

class PayrollKasbonReport extends Report
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Gaji & Kasbon';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Laporan Rekap Gaji & Kasbon';

    protected string $view = 'filament.pages.reports.payroll-kasbon-report';

    /**
     * Rekap total gaji yang sudah dibayar (status paid), per outlet, dalam periode pay_date.
     *
     * @return Collection<int, array{outlet_name: string, employee_count: int, total_base: float, total_bonus: float, total_kasbon_deduction: float, total_paid: float}>
     */
    public function getPayrollRows(): Collection
    {
        $query = Payroll::query()
            ->with('outlet')
            ->where('status', 'paid')
            ->whereBetween('pay_date', [$this->startDate, $this->endDate]);

        if ($this->outletId) {
            $query->where('outlet_id', $this->outletId);
        } elseif (! $this->isAdminUser()) {
            $query->where('outlet_id', OutletContext::currentOutletId());
        }

        return $query->get()
            ->groupBy('outlet_id')
            ->map(function (Collection $payrolls) {
                /** @var Payroll $first */
                $first = $payrolls->first();

                return [
                    'outlet_name' => $first->outlet?->name ?? '—',
                    'employee_count' => $payrolls->pluck('employee_id')->unique()->count(),
                    'total_base' => (float) $payrolls->sum('base_salary'),
                    'total_bonus' => (float) $payrolls->sum(fn (Payroll $p) => (float) $p->bonus_masuk + (float) $p->bonus_goreng),
                    'total_kasbon_deduction' => (float) $payrolls->sum('kasbon_deduction'),
                    'total_paid' => (float) $payrolls->sum('total_salary'),
                ];
            })
            ->values();
    }

    /**
     * Rekap gaji per outlet per periode (breakdown detail).
     *
     * @return Collection<int, array{period_label: string, period_start: string, outlet_name: string, employee_count: int, total_base: float, total_bonus: float, total_kasbon_deduction: float, total_paid: float}>
     */
    public function getPayrollByPeriodRows(): Collection
    {
        $query = Payroll::query()
            ->with(['outlet', 'employee'])
            ->where('status', 'paid')
            ->whereBetween('pay_date', [$this->startDate, $this->endDate]);

        if ($this->outletId) {
            $query->where('outlet_id', $this->outletId);
        } elseif (! $this->isAdminUser()) {
            $query->where('outlet_id', OutletContext::currentOutletId());
        }

        return $query->orderBy('period_start')
            ->orderBy('outlet_id')
            ->orderBy('employee_id')
            ->get()
            ->values();
    }

    /**
     * Saldo kasbon berjalan tiap karyawan aktif (bukan dibatasi periode filter —
     * ini snapshot saldo saat ini, bukan histori).
     *
     * @return Collection<int, array{name: string, outlet_name: string, outstanding: float}>
     */
    public function getOutstandingKasbonRows(): Collection
    {
        $query = Employee::query()->with('outlet')->where('status', 'active');

        if ($this->outletId) {
            $query->where('outlet_id', $this->outletId);
        } elseif (! $this->isAdminUser()) {
            $query->where('outlet_id', OutletContext::currentOutletId());
        }

        return $query->get()
            ->map(fn (Employee $employee) => [
                'name' => $employee->name,
                'outlet_name' => $employee->outlet?->name ?? '—',
                'outstanding' => $employee->outstandingKasbon(),
            ])
            ->filter(fn (array $row) => $row['outstanding'] > 0)
            ->sortByDesc('outstanding')
            ->values();
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function getSummary(): array
    {
        $payrollRows = $this->getPayrollRows();
        $periodRows = $this->getPayrollByPeriodRows();
        $kasbonRows = $this->getOutstandingKasbonRows();

        return [
            ['label' => 'Jumlah Periode', 'value' => $periodRows->pluck('period_start')->unique()->count().' periode'],
            ['label' => 'Total Gaji Dibayar', 'value' => $this->formatRupiah((float) $payrollRows->sum('total_paid'))],
            ['label' => 'Total Bonus', 'value' => $this->formatRupiah((float) $payrollRows->sum('total_bonus'))],
            ['label' => 'Potongan Kasbon', 'value' => $this->formatRupiah((float) $payrollRows->sum('total_kasbon_deduction'))],
            ['label' => 'Saldo Kasbon Berjalan', 'value' => $this->formatRupiah((float) $kasbonRows->sum('outstanding'))],
        ];
    }
}
