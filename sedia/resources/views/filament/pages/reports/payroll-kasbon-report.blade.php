<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    @php
        $payrollRows = $this->getPayrollRows();
        $kasbonRows = $this->getOutstandingKasbonRows();
    @endphp

    <x-report.kpi-grid :summary="$this->getSummary()" />

    <x-report.table
        heading="Rekap Gaji Dibayar"
        description="Berdasarkan tanggal gajian dalam periode filter, status Dibayar saja"
    >
        <x-slot name="head">
            <x-report.th>Outlet</x-report.th>
            <x-report.th align="right">Jumlah Karyawan</x-report.th>
            <x-report.th align="right">Total Gaji Pokok</x-report.th>
            <x-report.th align="right">Total Bonus</x-report.th>
            <x-report.th align="right">Potongan Kasbon</x-report.th>
            <x-report.th align="right">Total Dibayar</x-report.th>
        </x-slot>

        @forelse ($payrollRows as $row)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                <x-report.td strong>{{ $row['outlet_name'] }}</x-report.td>
                <x-report.td align="right">{{ number_format($row['employee_count']) }}</x-report.td>
                <x-report.td align="right">{{ $this->formatRupiah($row['total_base']) }}</x-report.td>
                <x-report.td align="right">{{ $this->formatRupiah($row['total_bonus']) }}</x-report.td>
                <x-report.td align="right">{{ $this->formatRupiah($row['total_kasbon_deduction']) }}</x-report.td>
                <x-report.td align="right" strong>{{ $this->formatRupiah($row['total_paid']) }}</x-report.td>
            </tr>
        @empty
            <x-report.empty-state :colspan="6" message="Belum ada gaji berstatus Dibayar pada periode ini." />
        @endforelse
    </x-report.table>

    <x-report.table
        heading="Saldo Kasbon Berjalan"
        description="Snapshot saat ini — hanya karyawan aktif dengan saldo di atas nol"
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
