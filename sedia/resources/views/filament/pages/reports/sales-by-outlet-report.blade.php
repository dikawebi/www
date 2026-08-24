<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    @php
        $rows = $this->getRows();
        $grandTotal = $this->getGrandTotal($rows);
    @endphp

    <div class="mt-6 overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Outlet</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Jumlah Transaksi</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Total Omzet</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Rata-rata / Transaksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($rows as $row)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row['outlet_name'] }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['trx_count']) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['total_omzet']) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['aov']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada transaksi selesai pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($rows->isNotEmpty())
                <tfoot class="bg-gray-50 font-semibold dark:bg-gray-800">
                    <tr>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">Total</td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">{{ number_format($grandTotal['trx_count']) }}</td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">{{ $this->formatRupiah($grandTotal['total_omzet']) }}</td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">{{ $this->formatRupiah($grandTotal['aov']) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</x-filament-panels::page>
