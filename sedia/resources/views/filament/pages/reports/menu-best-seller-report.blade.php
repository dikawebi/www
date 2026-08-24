<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    @php
        $rows = $this->getRows();
    @endphp

    <div class="mt-6 overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">#</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Menu</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Kategori</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Qty Terjual</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Total Omzet</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($rows as $i => $row)
                    <tr>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row['menu_name'] }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $row['category'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['qty_sold']) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['total_revenue']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada menu terjual pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
