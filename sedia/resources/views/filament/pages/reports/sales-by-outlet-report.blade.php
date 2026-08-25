<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    @php
        $rows = $this->getRows();
    @endphp

    <x-report.kpi-grid :summary="$this->getSummary()" />

    <x-report.table
        heading="Rincian per Outlet"
        description="Transaksi berstatus selesai dalam periode terpilih"
    >
        <x-slot name="head">
            <x-report.th>Outlet</x-report.th>
            <x-report.th align="right">Jumlah Transaksi</x-report.th>
            <x-report.th align="right">Total Omzet</x-report.th>
            <x-report.th align="right">Rata-rata / Transaksi</x-report.th>
        </x-slot>

        @forelse ($rows as $row)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                <x-report.td strong>{{ $row['outlet_name'] }}</x-report.td>
                <x-report.td align="right">{{ number_format($row['trx_count']) }}</x-report.td>
                <x-report.td align="right">{{ $this->formatRupiah($row['total_omzet']) }}</x-report.td>
                <x-report.td align="right">{{ $this->formatRupiah($row['aov']) }}</x-report.td>
            </tr>
        @empty
            <x-report.empty-state :colspan="4" message="Tidak ada transaksi selesai pada periode ini." />
        @endforelse
    </x-report.table>
</x-filament-panels::page>
