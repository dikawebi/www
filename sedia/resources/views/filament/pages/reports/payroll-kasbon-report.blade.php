<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    @php
        $periodRows = $this->getPayrollByPeriodRows();
        $kasbonRows = $this->getOutstandingKasbonRows();
        $totalPaid = $periodRows->sum(fn ($p) => (float) $p->total_salary);
        $totalBase = $periodRows->sum(fn ($p) => (float) $p->base_salary);
        $totalBonusMasuk = $periodRows->sum(fn ($p) => (float) $p->bonus_masuk);
        $totalBonusGoreng = $periodRows->sum(fn ($p) => (float) $p->bonus_goreng);
        $totalKasbon = $periodRows->sum(fn ($p) => (float) $p->kasbon_deduction);
    @endphp

    <x-report.kpi-grid :summary="$this->getSummary()" />

    <x-report.table
        heading="Detail Gaji Per Karyawan"
        description="Daftar gaji dibayar berdasarkan periode gajian (21–20), status Dibayar saja"
        footer="Total {{ $periodRows->count() }} baris · {{ $periodRows->pluck('outlet_id')->unique()->count() }} outlet · {{ $periodRows->pluck('employee_id')->unique()->count() }} karyawan"
    >
        <x-slot name="head">
            <x-report.th>Periode Gaji</x-report.th>
            <x-report.th>Karyawan</x-report.th>
            <x-report.th>Outlet</x-report.th>
            <x-report.th align="right">Gaji Pokok</x-report.th>
            <x-report.th align="right">Bonus Masuk</x-report.th>
            <x-report.th align="right">Bonus Goreng</x-report.th>
            <x-report.th align="right">Pot. Kasbon</x-report.th>
            <x-report.th align="right">Dibayar</x-report.th>
        </x-slot>

        @forelse ($periodRows as $row)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                <x-report.td>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $row->period_start->translatedFormat('d M Y') }} — {{ $row->period_end->translatedFormat('d M Y') }}</span>
                </x-report.td>
                <x-report.td>
                    <span class="font-medium text-sm text-gray-800 dark:text-gray-200">{{ $row->employee?->name ?? '—' }}</span>
                </x-report.td>
                <x-report.td class="text-gray-500 dark:text-gray-400 text-xs">{{ $row->outlet?->name ?? '—' }}</x-report.td>
                <x-report.td align="right" class="text-sm tabular-nums">{{ $this->formatRupiah($row->base_salary) }}</x-report.td>
                <x-report.td align="right" class="text-sm tabular-nums">{{ $this->formatRupiah($row->bonus_masuk) }}</x-report.td>
                <x-report.td align="right" class="text-sm tabular-nums">{{ $this->formatRupiah($row->bonus_goreng) }}</x-report.td>
                <x-report.td align="right" class="text-sm tabular-nums {{ $row->kasbon_deduction > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-gray-400 dark:text-gray-600' }}">
                    {{ $row->kasbon_deduction > 0 ? '-'.$this->formatRupiah($row->kasbon_deduction) : '—' }}
                </x-report.td>
                <x-report.td align="right" class="text-sm font-bold text-gray-900 dark:text-white tabular-nums">{{ $this->formatRupiah($row->total_salary) }}</x-report.td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="!py-8 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada gaji berstatus Dibayar pada periode ini.</td>
            </tr>
        @endforelse

        {{-- Footer ringkasan --}}
        @if($periodRows->isNotEmpty())
            <tr class="border-t border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-800/50">
                <td colspan="3" class="!py-3 !px-4 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Total ({{ $periodRows->count() }} baris)</td>
                <td data-align="right" class="!py-3 !px-4 text-xs font-bold text-gray-900 dark:text-white tabular-nums">{{ $this->formatRupiah($totalBase) }}</td>
                <td data-align="right" class="!py-3 !px-4 text-xs font-bold text-gray-900 dark:text-white tabular-nums">{{ $this->formatRupiah($totalBonusMasuk) }}</td>
                <td data-align="right" class="!py-3 !px-4 text-xs font-bold text-gray-900 dark:text-white tabular-nums">{{ $this->formatRupiah($totalBonusGoreng) }}</td>
                <td data-align="right" class="!py-3 !px-4 text-xs font-bold text-amber-700 dark:text-amber-400 tabular-nums">{{ $this->formatRupiah($totalKasbon) }}</td>
                <td data-align="right" class="!py-3 !px-4 text-xs font-black text-gray-900 dark:text-white tabular-nums">{{ $this->formatRupiah($totalPaid) }}</td>
            </tr>
        @endif
    </x-report.table>

    <x-report.table
        heading="Saldo Kasbon Berjalan"
        description="Snapshot saat ini — karyawan aktif dengan saldo di atas nol"
    >
        <x-slot name="head">
            <x-report.th>Karyawan</x-report.th>
            <x-report.th>Outlet</x-report.th>
            <x-report.th align="right">Saldo Kasbon</x-report.th>
        </x-slot>

        @forelse ($kasbonRows as $row)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                <x-report.td strong>{{ $row['name'] }}</x-report.td>
                <x-report.td>{{ $row['outlet_name'] }}</x-report.td>
                <x-report.td align="right">
                    <x-filament::badge color="warning">{{ $this->formatRupiah($row['outstanding']) }}</x-filament::badge>
                </x-report.td>
            </tr>
        @empty
            <x-report.empty-state :colspan="3" message="Tidak ada karyawan dengan saldo kasbon berjalan." />
        @endforelse
    </x-report.table>
</x-filament-panels::page>
