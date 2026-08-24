<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    @php
        $rows = $this->getRows();
        $grandTotal = $this->getGrandTotal($rows);
    @endphp

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-filament::section>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Total Omzet</p>
            <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $this->formatRupiah($grandTotal['total_omzet']) }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Jumlah Transaksi</p>
            <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($grandTotal['trx_count']) }}</p>
        </x-filament::section>
        <x-filament::section>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Rata-rata / Transaksi</p>
            <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $this->formatRupiah($grandTotal['aov']) }}</p>
        </x-filament::section>
    </div>

    <x-filament::section class="mt-6">
        <x-slot name="heading">Rincian per Outlet</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800 text-xs uppercase font-semibold text-gray-600 dark:text-gray-400">
                        <th class="px-6 py-3">Outlet</th>
                        <th class="px-6 py-3 text-right">Jumlah Transaksi</th>
                        <th class="px-6 py-3 text-right">Total Omzet</th>
                        <th class="px-6 py-3 text-right">Rata-rata / Transaksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $row['outlet_name'] }}</td>
                            <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['trx_count']) }}</td>
                            <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['total_omzet']) }}</td>
                            <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['aov']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">Tidak ada transaksi selesai pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($rows->isNotEmpty())
                    <tfoot class="border-t border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800 font-semibold text-gray-900 dark:text-white">
                        <tr>
                            <td class="px-6 py-4">Total</td>
                            <td class="px-6 py-4 text-right">{{ number_format($grandTotal['trx_count']) }}</td>
                            <td class="px-6 py-4 text-right">{{ $this->formatRupiah($grandTotal['total_omzet']) }}</td>
                            <td class="px-6 py-4 text-right">{{ $this->formatRupiah($grandTotal['aov']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
