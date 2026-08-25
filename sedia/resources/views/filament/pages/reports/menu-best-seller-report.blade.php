<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    @php
        $rows = $this->getRows();
    @endphp

    <x-report.kpi-grid :summary="$this->getSummary()" />

    <x-report.table
        heading="Ranking Menu"
        description="Berdasarkan jumlah dan omzet"
    >
        <x-slot name="head">
            <x-report.th>#</x-report.th>
            <x-report.th>Menu</x-report.th>
            <x-report.th>Kategori</x-report.th>
            <x-report.th align="right">Qty Terjual</x-report.th>
            <x-report.th align="right">Total Omzet</x-report.th>
        </x-slot>

        @forelse ($rows as $i => $row)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                <x-report.td>
                    <x-filament::badge color="warning" size="lg">{{ $i + 1 }}</x-filament::badge>
                </x-report.td>
                <x-report.td strong>{{ $row['menu_name'] }}</x-report.td>
                <x-report.td>
                    @if ($row['category'])
                        <x-filament::badge color="gray">{{ $row['category'] }}</x-filament::badge>
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </x-report.td>
                <x-report.td align="right">{{ number_format($row['qty_sold']) }}</x-report.td>
                <x-report.td align="right" strong>{{ $this->formatRupiah($row['total_revenue']) }}</x-report.td>
            </tr>
        @empty
            <x-report.empty-state :colspan="5" message="Tidak ada menu terjual pada periode ini." />
        @endforelse
    </x-report.table>
</x-filament-panels::page>
