<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    @php
        $rows = $this->getRows();
        $grandTotal = $this->getGrandTotal($rows);
    @endphp

    {{-- KPI summary --}}
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Omzet</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $this->formatRupiah($grandTotal['total_omzet']) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Jumlah Transaksi</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($grandTotal['trx_count']) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Rata-rata / Transaksi</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $this->formatRupiah($grandTotal['aov']) }}</p>
        </div>
    </div>

    {{-- Table --}}
    <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-white/10">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Rincian per Outlet</h3>
            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-300">
                {{ $rows->count() }} outlet
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-white/10">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Outlet</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Jumlah Transaksi</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Omzet</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Rata-rata / Transaksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($rows as $row)
                        <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="px-5 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $row['outlet_name'] }}</td>
                            <td class="px-5 py-3 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">{{ number_format($row['trx_count']) }}</td>
                            <td class="px-5 py-3 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['total_omzet']) }}</td>
                            <td class="px-5 py-3 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['aov']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                                Tidak ada transaksi selesai pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($rows->isNotEmpty())
                    <tfoot class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <td class="px-5 py-3 text-sm font-semibold text-gray-900 dark:text-white">Total</td>
                            <td class="px-5 py-3 text-right text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ number_format($grandTotal['trx_count']) }}</td>
                            <td class="px-5 py-3 text-right text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ $this->formatRupiah($grandTotal['total_omzet']) }}</td>
                            <td class="px-5 py-3 text-right text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ $this->formatRupiah($grandTotal['aov']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</x-filament-panels::page>
