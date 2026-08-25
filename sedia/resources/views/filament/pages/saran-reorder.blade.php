<x-filament-panels::page>
    <x-filament::section class="report-no-print">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            @if ($this->isAdminUser())
                <div class="space-y-2 min-w-[220px]">
                    <label class="text-sm font-medium leading-6 text-gray-950 dark:text-white">Outlet</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select name="outlet_id">
                            @foreach ($this->outletOptions() as $id => $name)
                                <option value="{{ $id }}" @selected($outletId == $id)>{{ $name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            @endif
            <x-filament::button type="submit" icon="heroicon-m-magnifying-glass">Tampilkan</x-filament::button>
            <x-filament::button type="button" color="gray" icon="heroicon-m-printer" onclick="window.sediaPrintReport()">Cetak</x-filament::button>
        </form>
        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Dihitung dari pemakaian 30 hari terakhir (penjualan + rusak/expired + transfer keluar). Target = min stock + 7 hari pemakaian rata-rata.</p>
    </x-filament::section>

    @include('filament.pages.reports.partials.print-styles')

    @php $rows = $this->getRows(); @endphp

    <div class="report-print-header">
        <p class="report-print-header-title">{{ config('app.name') }} — Saran Reorder</p>
        <p class="report-print-header-meta">Outlet: {{ $outletId ? (\App\Models\Outlet::find($outletId)->name ?? '-') : '-' }} · Dicetak: {{ now()->translatedFormat('d M Y H:i') }}</p>
    </div>

    @if (! $outletId)
        <x-filament::section>
            <p class="text-sm text-gray-500">Pilih outlet terlebih dahulu.</p>
        </x-filament::section>
    @else
        <x-report.table heading="Perlu Reorder" :description="'Outlet '.(\App\Models\Outlet::find($outletId)->name ?? '-').' — hanya tampil bahan di bawah min stock atau butuh tambah untuk 7 hari ke depan'">
            <x-slot name="head">
                <x-report.th>Bahan Baku</x-report.th>
                <x-report.th>Satuan</x-report.th>
                <x-report.th align="right">Stok Saat Ini</x-report.th>
                <x-report.th align="right">Min Stock</x-report.th>
                <x-report.th align="right">Rata-/hari</x-report.th>
                <x-report.th align="right">Sisa Hari</x-report.th>
                <x-report.th align="right">Saran Order</x-report.th>
            </x-slot>
            @forelse ($rows as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                    <x-report.td strong>{{ $row['ingredient_name'] }}</x-report.td>
                    <x-report.td><x-filament::badge color="gray">{{ $row['unit'] }}</x-filament::badge></x-report.td>
                    <x-report.td align="right" :tone="$row['current'] < $row['min_stock'] ? 'danger' : 'default'">{{ number_format($row['current'], 2) }}</x-report.td>
                    <x-report.td align="right">{{ number_format($row['min_stock'], 2) }}</x-report.td>
                    <x-report.td align="right">{{ number_format($row['avg_daily'], 2) }}</x-report.td>
                    <x-report.td align="right">{{ $row['days_remaining'] === null ? '∞' : number_format($row['days_remaining'], 1).' h' }}</x-report.td>
                    <x-report.td align="right" strong tone="success">{{ number_format($row['suggested'], 0) }}</x-report.td>
                </tr>
            @empty
                <x-report.empty-state :colspan="7" message="Semua stok aman — tidak ada saran reorder saat ini." />
            @endforelse
        </x-report.table>
    @endif
</x-filament-panels::page>
