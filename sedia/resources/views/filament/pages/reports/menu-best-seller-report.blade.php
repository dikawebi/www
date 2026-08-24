<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Ranking Menu
        </x-slot>
        
        <x-slot name="description">
            Berdasarkan jumlah dan omzet
        </x-slot>

        @php
            $rows = $this->getRows();
        @endphp

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">#</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Menu</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Kategori</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Qty Terjual</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Total Omzet</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($rows as $i => $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-6 py-4">
                                <x-filament::badge color="warning" size="lg">
                                    {{ $i + 1 }}
                                </x-filament::badge>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $row['menu_name'] }}</td>
                            <td class="px-6 py-4">
                                @if ($row['category'])
                                    <x-filament::badge color="gray">
                                        {{ $row['category'] }}
                                    </x-filament::badge>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['qty_sold']) }}</td>
                            <td class="px-6 py-4 text-right font-medium text-gray-900 dark:text-white">{{ $this->formatRupiah($row['total_revenue']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                Tidak ada menu terjual pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>

