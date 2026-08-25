<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    @php
        $rows = $this->getRows();
    @endphp

    <x-report.kpi-grid :summary="$this->getSummary()" />

    <x-report.table
        heading="Laba Kotor per Menu"
        description="Diurutkan dari laba kotor terbesar"
        footer="HPP dihitung dari resep menu x harga beli per unit bahan baku. Kalau HPP masih 0 semua, berarti harga beli bahan bakunya belum diisi di menu Bahan Baku."
    >
        <x-slot name="head">
            <x-report.th>Menu</x-report.th>
            <x-report.th align="right">Harga Jual</x-report.th>
            <x-report.th align="right">HPP / Unit</x-report.th>
            <x-report.th align="right">Margin / Unit</x-report.th>
            <x-report.th align="right">Qty Terjual</x-report.th>
            <x-report.th align="right">Omzet</x-report.th>
            <x-report.th align="right">Laba Kotor</x-report.th>
            <x-report.th align="right">Margin %</x-report.th>
        </x-slot>

        @forelse ($rows as $row)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                <x-report.td strong>{{ $row['menu_name'] }}</x-report.td>
                <x-report.td align="right">{{ $this->formatRupiah($row['price']) }}</x-report.td>
                <x-report.td align="right">{{ $this->formatRupiah($row['hpp_per_unit']) }}</x-report.td>
                <x-report.td align="right">{{ $this->formatRupiah($row['margin_per_unit']) }}</x-report.td>
                <x-report.td align="right">{{ number_format($row['qty_sold']) }}</x-report.td>
                <x-report.td align="right">{{ $this->formatRupiah($row['revenue']) }}</x-report.td>
                <x-report.td
                    align="right"
                    strong
                    :tone="$row['gross_margin'] < 0 ? 'danger' : 'success'"
                >
                    {{ $this->formatRupiah($row['gross_margin']) }}
                </x-report.td>
                <x-report.td align="right">{{ number_format($row['margin_pct'], 1) }}%</x-report.td>
            </tr>
        @empty
            <x-report.empty-state :colspan="8" message="Tidak ada menu terjual pada periode ini." />
        @endforelse
    </x-report.table>
</x-filament-panels::page>
