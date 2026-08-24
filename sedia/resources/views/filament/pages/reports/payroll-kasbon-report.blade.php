<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    @php
        $payrollRows = $this->getPayrollRows();
        $kasbonRows = $this->getOutstandingKasbonRows();
    @endphp

    <x-filament::section class="mt-6">
        <x-slot name="heading">Rekap Gaji Dibayar</x-slot>
        <x-slot name="description">Berdasarkan Tanggal Gajian dalam periode filter, status "Dibayar" saja</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800 text-xs uppercase font-semibold text-gray-600 dark:text-gray-400">
                        <th class="px-6 py-3">Outlet</th>
                        <th class="px-6 py-3 text-right">Jumlah Karyawan</th>
                        <th class="px-6 py-3 text-right">Total Gaji Pokok</th>
                        <th class="px-6 py-3 text-right">Total Bonus</th>
                        <th class="px-6 py-3 text-right">Total Potongan Kasbon</th>
                        <th class="px-6 py-3 text-right">Total Dibayar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($payrollRows as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $row['outlet_name'] }}</td>
                            <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['employee_count']) }}</td>
                            <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['total_base']) }}</td>
                            <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['total_bonus']) }}</td>
                            <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['total_kasbon_deduction']) }}</td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">{{ $this->formatRupiah($row['total_paid']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">Belum ada gaji berstatus "Dibayar" pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament::section class="mt-6">
        <x-slot name="heading">Saldo Kasbon Berjalan</x-slot>
        <x-slot name="description">Snapshot saat ini — hanya karyawan aktif dengan saldo > 0</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800 text-xs uppercase font-semibold text-gray-600 dark:text-gray-400">
                        <th class="px-6 py-3">Karyawan</th>
                        <th class="px-6 py-3">Outlet</th>
                        <th class="px-6 py-3 text-right">Saldo Kasbon</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($kasbonRows as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $row['name'] }}</td>
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ $row['outlet_name'] }}</td>
                            <td class="px-6 py-4 text-right">
                                <x-filament::badge color="warning">{{ $this->formatRupiah($row['outstanding']) }}</x-filament::badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500">Tidak ada karyawan dengan saldo kasbon berjalan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
