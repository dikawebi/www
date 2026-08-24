<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    @php
        $rows = $this->getRows();
    @endphp

    <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-white/10">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Laba Kotor per Menu</h3>
                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Diurutkan dari laba kotor terbesar</p>
            </div>
            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-300">
                {{ $rows->count() }} menu
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-white/10">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Menu</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Harga Jual</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">HPP / Unit</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Margin / Unit</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Qty Terjual</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Omzet</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Laba Kotor</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Margin %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($rows as $row)
                        <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="px-5 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $row['menu_name'] }}</td>
                            <td class="px-5 py-3 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['price']) }}</td>
                            <td class="px-5 py-3 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['hpp_per_unit']) }}</td>
                            <td class="px-5 py-3 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['margin_per_unit']) }}</td>
                            <td class="px-5 py-3 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">{{ number_format($row['qty_sold']) }}</td>
                            <td class="px-5 py-3 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['revenue']) }}</td>
                            <td class="px-5 py-3 text-right text-sm font-semibold tabular-nums {{ $row['gross_margin'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ $this->formatRupiah($row['gross_margin']) }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold tabular-nums
                                    {{ $row['margin_pct'] < 0
                                        ? 'bg-red-100 text-red-700 dark:bg-red-400/10 dark:text-red-400'
                                        : 'bg-green-100 text-green-700 dark:bg-green-400/10 dark:text-green-400' }}">
                                    {{ number_format($row['margin_pct'], 1) }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                                Tidak ada menu terjual pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 px-5 py-3 dark:border-white/10">
            <p class="text-xs text-gray-400 dark:text-gray-500">
                HPP dihitung dari resep menu × harga beli/unit bahan baku. Kalau HPP masih 0 semua, berarti harga
                beli bahan bakunya belum diisi di menu Bahan Baku.
            </p>
        </div>
    </div>
</x-filament-panels::page>
