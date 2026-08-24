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
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Terpakai (Penjualan)</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Rusak/Expired</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Transfer Keluar</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Total Keluar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($rows as $row)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row['ingredient_name'] }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $row['unit'] }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['sale_qty'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['waste_qty'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['transfer_out_qty'], 2) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($row['total_out_qty'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada pergerakan stok keluar pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
        "Terpakai (Penjualan)" = potongan stok otomatis dari transaksi penjualan. "Rusak/Expired" = stok yang dibuang
        karena expired/reject. "Transfer Keluar" = stok yang dikirim ke outlet lain.
    </p>
</x-filament-panels::page>
