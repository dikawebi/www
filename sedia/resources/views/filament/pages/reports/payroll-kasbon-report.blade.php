<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    @php
        $payrollRows = $this->getPayrollRows();
        $kasbonRows = $this->getOutstandingKasbonRows();
    @endphp

    <h3 class="mt-6 text-base font-semibold text-gray-900 dark:text-white">Rekap Gaji Dibayar (per Outlet)</h3>
    <p class="text-xs text-gray-500 dark:text-gray-400">Berdasarkan Tanggal Gajian dalam periode filter, status "Dibayar" saja.</p>

    <div class="mt-2 overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Outlet</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Jumlah Karyawan</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Total Gaji Pokok</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Total Bonus</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Total Potongan Kasbon</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Total Dibayar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($payrollRows as $row)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row['outlet_name'] }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['employee_count']) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['total_base']) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['total_bonus']) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['total_kasbon_deduction']) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ $this->formatRupiah($row['total_paid']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                            Belum ada gaji berstatus "Dibayar" pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h3 class="mt-8 text-base font-semibold text-gray-900 dark:text-white">Saldo Kasbon Berjalan (Karyawan Aktif)</h3>
    <p class="text-xs text-gray-500 dark:text-gray-400">Snapshot saldo kasbon saat ini (bukan dibatasi periode filter di atas) — hanya karyawan dengan saldo &gt; 0.</p>

    <div class="mt-2 overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Karyawan</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Outlet</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Saldo Kasbon</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($kasbonRows as $row)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row['name'] }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $row['outlet_name'] }}</td>
                        <td class="px-4 py-3 text-right text-amber-600 dark:text-amber-400">{{ $this->formatRupiah($row['outstanding']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada karyawan dengan saldo kasbon berjalan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
