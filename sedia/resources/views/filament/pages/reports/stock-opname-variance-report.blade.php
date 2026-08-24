<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    @php
        $rows = $this->getRows();
    @endphp

    <div class="mt-6 overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Bahan Baku</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Satuan</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Jumlah Opname</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Selisih Bersih</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Total Selisih (Absolut)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($rows as $row)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row['ingredient_name'] }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $row['unit'] }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['opname_count']) }}</td>
                        <td class="px-4 py-3 text-right {{ $row['net_difference'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            {{ $row['net_difference'] > 0 ? '+' : '' }}{{ number_format($row['net_difference'], 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($row['abs_difference'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada stock opname yang sudah diterapkan pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
        Hanya menghitung stock opname berstatus "Diterapkan". Selisih Bersih negatif (merah) = stok fisik lebih
        sedikit dari catatan sistem (potensi susut/kehilangan). Diurutkan dari selisih terbesar.
    </p>
</x-filament-panels::page>
