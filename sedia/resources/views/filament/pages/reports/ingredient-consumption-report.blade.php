<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    @php
        $rows = $this->getRows();
    @endphp

    <x-report.kpi-grid :summary="$this->getSummary()" />

    <x-report.table
        heading="Pemakaian per Bahan Baku"
        description="Diurutkan dari total keluar terbesar"
        footer="Terpakai (Penjualan) = potongan stok otomatis dari transaksi. Rusak/Expired = stok dibuang. Transfer Keluar = stok ke outlet lain. Estimasi Nilai = total keluar x harga beli per unit."
    >
        <x-slot name="head">
            <x-report.th>Bahan Baku</x-report.th>
            <x-report.th>Satuan</x-report.th>
            <x-report.th align="right">Terpakai (Penjualan)</x-report.th>
            <x-report.th align="right">Rusak / Expired</x-report.th>
            <x-report.th align="right">Transfer Keluar</x-report.th>
            <x-report.th align="right">Total Keluar</x-report.th>
            <x-report.th align="right">Estimasi Nilai</x-report.th>
        </x-slot>

        @forelse ($rows as $row)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                <x-report.td strong>{{ $row['ingredient_name'] }}</x-report.td>
                <x-report.td>
                    <x-filament::badge color="gray">{{ $row['unit'] }}</x-filament::badge>
                </x-report.td>
                <x-report.td align="right">{{ number_format($row['sale_qty'], 2) }}</x-report.td>
                <x-report.td align="right">{{ number_format($row['waste_qty'], 2) }}</x-report.td>
                <x-report.td align="right">{{ number_format($row['transfer_out_qty'], 2) }}</x-report.td>
                <x-report.td align="right" strong>{{ number_format($row['total_out_qty'], 2) }}</x-report.td>
                <x-report.td align="right">{{ $this->formatRupiah($row['est_value']) }}</x-report.td>
            </tr>
        @empty
            <x-report.empty-state :colspan="7" message="Tidak ada pergerakan stok keluar pada periode ini." />
        @endforelse
    </x-report.table>
</x-filament-panels::page>
