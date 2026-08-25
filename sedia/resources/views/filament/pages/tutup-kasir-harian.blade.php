<x-filament-panels::page>
    <x-filament::section class="report-no-print">
        <form method="GET" class="report-filter-grid">
            <div class="space-y-2">
                <label class="text-sm font-medium leading-6 text-gray-950 dark:text-white">Tanggal</label>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" name="date" value="{{ $selectedDate }}" />
                </x-filament::input.wrapper>
            </div>
            @if ($this->isAdminUser())
                <div class="space-y-2">
                    <label class="text-sm font-medium leading-6 text-gray-950 dark:text-white">Outlet</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select name="outlet_id">
                            <option value="">Semua Outlet</option>
                            @foreach ($this->outletOptions() as $id => $name)
                                <option value="{{ $id }}" @selected($outletId == $id)>{{ $name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            @endif
            <div class="flex items-end gap-3">
                <x-filament::button type="submit" icon="heroicon-m-magnifying-glass">Tampilkan</x-filament::button>
                <x-filament::button type="button" color="gray" icon="heroicon-m-printer" onclick="window.sediaPrintReport()">Cetak</x-filament::button>
            </div>
        </form>
    </x-filament::section>

    @include('filament.pages.reports.partials.print-styles')

    @php
        $rows = $this->getRows();
        $grand = $this->getGrandTotal($rows);
    @endphp

    <div class="report-print-header">
        <p class="report-print-header-title">{{ config('app.name') }} — Tutup Kasir Harian</p>
        <p class="report-print-header-meta">Outlet: {{ $this->currentOutletName() }} · Tanggal: {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d M Y') }} · Dicetak: {{ now()->translatedFormat('d M Y H:i') }}</p>
    </div>

    <x-report.kpi-grid :summary="[
        ['label' => 'Total Transaksi', 'value' => number_format($grand['trx_count'])],
        ['label' => 'Total Omzet', 'value' => $this->formatRupiah($grand['total'])],
        ['label' => 'Tunai', 'value' => $this->formatRupiah($grand['by_payment']['cash'] ?? 0)],
        ['label' => 'Non-Tunai', 'value' => $this->formatRupiah(($grand['by_payment']['transfer'] ?? 0) + ($grand['by_payment']['qris'] ?? 0) + ($grand['by_payment']['debit'] ?? 0))],
    ]" />

    <x-report.table heading="Rincian per Kasir" :description="'Tanggal '.$selectedDate.' · '.$this->currentOutletName()">
        <x-slot name="head">
            <x-report.th>Kasir</x-report.th>
            <x-report.th align="right">Transaksi</x-report.th>
            <x-report.th align="right">Tunai</x-report.th>
            <x-report.th align="right">Transfer</x-report.th>
            <x-report.th align="right">QRIS</x-report.th>
            <x-report.th align="right">Debit</x-report.th>
            <x-report.th align="right">Total</x-report.th>
        </x-slot>
        @forelse ($rows as $row)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                <x-report.td strong>{{ $row['cashier_name'] }}</x-report.td>
                <x-report.td align="right">{{ number_format($row['trx_count']) }}</x-report.td>
                <x-report.td align="right">{{ $this->formatRupiah($row['by_payment']['cash'] ?? 0) }}</x-report.td>
                <x-report.td align="right">{{ $this->formatRupiah($row['by_payment']['transfer'] ?? 0) }}</x-report.td>
                <x-report.td align="right">{{ $this->formatRupiah($row['by_payment']['qris'] ?? 0) }}</x-report.td>
                <x-report.td align="right">{{ $this->formatRupiah($row['by_payment']['debit'] ?? 0) }}</x-report.td>
                <x-report.td align="right" strong>{{ $this->formatRupiah($row['total']) }}</x-report.td>
            </tr>
        @empty
            <x-report.empty-state :colspan="7" message="Tidak ada transaksi selesai pada tanggal ini." />
        @endforelse
    </x-report.table>
</x-filament-panels::page>
