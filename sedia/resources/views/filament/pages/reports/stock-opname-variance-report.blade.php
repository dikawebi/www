<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    <x-filament::section class="mt-6">
        <x-slot name="heading">Selisih Stock Opname</x-slot>
        <x-slot name="description">Hanya opname berstatus "Diterapkan", diurutkan dari selisih terbesar</x-slot>

        @php
            $rows = $this->getRows();
        @endphp

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800 text-xs uppercase font-semibold text-gray-600 dark:text-gray-400">
                        <th class="px-6 py-3">Bahan Baku</th>
                        <th class="px-6 py-3">Satuan</th>
                        <th class="px-6 py-3 text-right">Jumlah Opname</th>
                        <th class="px-6 py-3 text-right">Selisih Bersih</th>
                        <th class="px-6 py-3 text-right">Total Selisih (Absolut)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $row['ingredient_name'] }}</td>
                            <td class="px-6 py-4"><x-filament::badge color="gray">{{ $row['unit'] }}</x-filament::badge></td>
                            <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['opname_count']) }}</td>
                            <td class="px-6 py-4 text-right font-medium {{ $row['net_difference'] < 0 ? 'text-danger-600' : ($row['net_difference'] > 0 ? 'text-success-600' : 'text-gray-700') }}">
                                {{ $row['net_difference'] > 0 ? '+' : '' }}{{ number_format($row['net_difference'], 2) }}
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($row['abs_difference'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">Tidak ada stock opname yang sudah diterapkan pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-slot name="footer">
            <p class="text-xs text-gray-500">Merah = stok fisik kurang dari catatan sistem (potensi susut). Hijau = stok fisik lebih banyak dari catatan.</p>
        </x-slot>
    </x-filament::section>
</x-filament-panels::page>

