<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    @php
        $rows = $this->getRows();
        $rankBadge = ['bg-amber-100 text-amber-700 dark:bg-amber-400/10 dark:text-amber-400', 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300', 'bg-orange-100 text-orange-700 dark:bg-orange-400/10 dark:text-orange-400'];
    @endphp

    <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-white/10">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Ranking Menu</h3>
            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-300">
                {{ $rows->count() }} menu
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-white/10">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">#</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Menu</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Kategori</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Qty Terjual</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Omzet</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($rows as $i => $row)
                        <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="px-5 py-3">
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold {{ $rankBadge[$i] ?? 'bg-gray-50 text-gray-500 dark:bg-white/5 dark:text-gray-400' }}">
                                    {{ $i + 1 }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $row['menu_name'] }}</td>
                            <td class="px-5 py-3">
                                @if ($row['category'])
                                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-300">
                                        {{ $row['category'] }}
                                    </span>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">{{ number_format($row['qty_sold']) }}</td>
                            <td class="px-5 py-3 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['total_revenue']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                                Tidak ada menu terjual pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
