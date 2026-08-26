<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    @php
        $rows = $this->getRows();
        $perOutlet = $this->getPerOutletRows();
    @endphp

    <x-report.kpi-grid :summary="$this->getSummary()" />

    <x-report.table heading="Rekap per Outlet" description="Total pengeluaran per outlet dalam periode">
        <x-slot name="head">
            <x-report.th>Outlet</x-report.th>
            <x-report.th align="right">Jml Transaksi</x-report.th>
            <x-report.th align="right">Total Pengeluaran</x-report.th>
        </x-slot>
        @forelse ($perOutlet as $row)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                <x-report.td strong>{{ $row['outlet_name'] }}</x-report.td>
                <x-report.td align="right">{{ number_format($row['trx_count']) }}</x-report.td>
                <x-report.td align="right" strong>{{ $this->formatRupiah($row['total_amount']) }}</x-report.td>
            </tr>
        @empty
            <x-report.empty-state :colspan="3" message="Tidak ada pengeluaran pada periode ini." />
        @endforelse
    </x-report.table>

    <x-report.table heading="Rincian per Kategori" description="Diurutkan dari total terbesar">
        <x-slot name="head">
            <x-report.th>Outlet</x-report.th>
            <x-report.th>Kategori</x-report.th>
            <x-report.th align="right">Transaksi</x-report.th>
            <x-report.th align="right">Total</x-report.th>
        </x-slot>
        @forelse ($rows as $row)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                <x-report.td>{{ $row['outlet_name'] }}</x-report.td>
                <x-report.td><x-filament::badge color="gray">{{ ucfirst(str_replace('_',' ', $row['category'])) }}</x-filament::badge></x-report.td>
                <x-report.td align="right">{{ number_format($row['trx_count']) }}</x-report.td>
                <x-report.td align="right" strong>{{ $this->formatRupiah($row['total_amount']) }}</x-report.td>
            </tr>
        @empty
            <x-report.empty-state :colspan="4" message="Tidak ada data." />
        @endforelse
    </x-report.table>
</x-filament-panels::page>
