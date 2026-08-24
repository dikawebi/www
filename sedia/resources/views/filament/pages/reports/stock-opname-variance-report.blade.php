<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    @php
        $rows = $this->getRows();
    @endphp

    <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-white/10">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Selisih Stock Opname</h3>
                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Hanya opname berstatus "Diterapkan", diurutkan dari selisih terbesar</p>
            </div>
            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-300">
                {{ $rows->count() }} bahan
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-white/10">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Bahan Baku</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Satuan</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Jumlah Opname</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Selisih Bersih</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Selisih (Absolut)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($rows as $row)
                        <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="px-5 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $row['ingredient_name'] }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-300">
                                    {{ $row['unit'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">{{ number_format($row['opname_count']) }}</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold tabular-nums
                                    {{ $row['net_difference'] < 0
                                        ? 'bg-red-100 text-red-700 dark:bg-red-400/10 dark:text-red-400'
                                        : ($row['net_difference'] > 0 ? 'bg-green-100 text-green-700 dark:bg-green-400/10 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400') }}">
                                    {{ $row['net_difference'] > 0 ? '+' : '' }}{{ number_format($row['net_difference'], 2) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ number_format($row['abs_difference'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                                Tidak ada stock opname yang sudah diterapkan pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 px-5 py-3 dark:border-white/10">
            <p class="text-xs text-gray-400 dark:text-gray-500">
                Selisih Bersih merah = stok fisik lebih sedikit dari catatan sistem (potensi susut/kehilangan).
                Hijau = stok fisik lebih banyak dari catatan sistem.
            </p>
        </div>
    </div>
</x-filament-panels::page>
