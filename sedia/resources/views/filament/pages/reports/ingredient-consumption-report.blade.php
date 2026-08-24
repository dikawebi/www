<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Pemakaian per Bahan Baku
        </x-slot>
        
        <x-slot name="description">
            Diurutkan dari total keluar terbesar
        </x-slot>

        @php
            $rows = $this->getRows();
        @endphp

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Bahan Baku</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Satuan</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Terpakai (Penjualan)</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Rusak / Expired</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Transfer Keluar</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Total Keluar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $row['ingredient_name'] }}</td>
                            <td class="px-6 py-4">
                                <x-filament::badge color="gray">
                                    {{ $row['unit'] }}
                                </x-filament::badge>
                            </td>
                            <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['sale_qty'], 2) }}</td>
                            <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['waste_qty'], 2) }}</td>
                            <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['transfer_out_qty'], 2) }}</td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($row['total_out_qty'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                Tidak ada pergerakan stok keluar pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-slot name="footer">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                "Terpakai (Penjualan)" = potongan stok otomatis dari transaksi. "Rusak/Expired" = stok dibuang. "Transfer Keluar" = stok ke outlet lain.
            </p>
        </x-slot>
    </x-filament::section>
</x-filament-panels::page>

