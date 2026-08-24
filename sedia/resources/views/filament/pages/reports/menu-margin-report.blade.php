<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Laba Kotor per Menu
        </x-slot>
        
        <x-slot name="description">
            Diurutkan dari laba kotor terbesar
        </x-slot>

        @php
            $rows = $this->getRows();
        @endphp

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Menu</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Harga Jual</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">HPP / Unit</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Margin / Unit</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Qty Terjual</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Omzet</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Laba Kotor</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Margin %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $row['menu_name'] }}</td>
                            <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['price']) }}</td>
                            <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['hpp_per_unit']) }}</td>
                            <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['margin_per_unit']) }}</td>
                            <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['qty_sold']) }}</td>
                            <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['revenue']) }}</td>
                            <td class="px-6 py-4 text-right font-semibold {{ $row['gross_margin'] < 0 ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400' }}">
                                {{ $this->formatRupiah($row['gross_margin']) }}
                            </td>
                            <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['margin_pct'], 1) }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                Tidak ada menu terjual pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <x-slot name="footer">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                HPP dihitung dari resep menu × harga beli/unit bahan baku. Kalau HPP masih 0 semua, berarti harga beli bahan bakunya belum diisi di menu Bahan Baku.
            </p>
        </x-slot>
    </x-filament::section>
</x-filament-panels::page>

