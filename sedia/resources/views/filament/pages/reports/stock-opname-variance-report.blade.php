<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    @php
        $rows = $this->getRows();
    @endphp

    <x-report.kpi-grid :summary="$this->getSummary()" />

    <x-report.table
        heading="Selisih Stock Opname"
        description="Hanya opname berstatus Diterapkan, diurutkan dari selisih terbesar"
        footer="Merah = stok fisik kurang dari catatan sistem (potensi susut). Hijau = stok fisik lebih banyak dari catatan."
    >
        <x-slot name="head">
            <x-report.th>Bahan Baku</x-report.th>
            <x-report.th>Satuan</x-report.th>
            <x-report.th align="right">Jumlah Opname</x-report.th>
            <x-report.th align="right">Selisih Bersih</x-report.th>
            <x-report.th align="right">Total Selisih (Absolut)</x-report.th>
        </x-slot>

        @forelse ($rows as $row)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                <x-report.td strong>{{ $row['ingredient_name'] }}</x-report.td>
                <x-report.td>
                    <x-filament::badge color="gray">{{ $row['unit'] }}</x-filament::badge>
                </x-report.td>
                <x-report.td align="right">{{ number_format($row['opname_count']) }}</x-report.td>
                <x-report.td
                    align="right"
                    strong
                    :tone="$row['net_difference'] < 0 ? 'danger' : ($row['net_difference'] > 0 ? 'success' : 'default')"
                >
                    {{ $row['net_difference'] > 0 ? '+' : '' }}{{ number_format($row['net_difference'], 2) }}
                </x-report.td>
                <x-report.td align="right">{{ number_format($row['abs_difference'], 2) }}</x-report.td>
            </tr>
        @empty
            <x-report.empty-state :colspan="5" message="Tidak ada stock opname yang sudah diterapkan pada periode ini." />
        @endforelse
    </x-report.table>
</x-filament-panels::page>
