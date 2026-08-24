<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    @php
        $rows = $this->getRows();
    @endphp

    <div class="mt-6 overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Menu</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Harga Jual</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">HPP / Unit</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Margin / Unit</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Qty Terjual</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Omzet</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Laba Kotor</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Margin %</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($rows as $row)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row['menu_name'] }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['price']) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['hpp_per_unit']) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['margin_per_unit']) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['qty_sold']) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['revenue']) }}</td>
                        <td class="px-4 py-3 text-right font-semibold {{ $row['gross_margin'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            {{ $this->formatRupiah($row['gross_margin']) }}
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['margin_pct'], 1) }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada menu terjual pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
        HPP (Harga Pokok Penjualan) dihitung dari resep menu × harga beli/unit bahan baku (kolom "Harga beli/unit" di
        menu Bahan Baku). Kalau HPP masih 0 semua, berarti harga beli bahan bakunya belum diisi.
    </p>
</x-filament-panels::page>
