<x-filament-panels::page>
    @include('filament.pages.reports.partials.filter-form')

    @php
        $payrollRows = $this->getPayrollRows();
        $kasbonRows = $this->getOutstandingKasbonRows();
    @endphp

    {{-- Section: Rekap Gaji --}}
    <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-white/10">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Rekap Gaji Dibayar</h3>
                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Berdasarkan Tanggal Gajian dalam periode filter, status "Dibayar" saja</p>
            </div>
            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-300">
                {{ $payrollRows->count() }} outlet
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-white/10">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Outlet</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Jumlah Karyawan</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Gaji Pokok</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Bonus</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Potongan Kasbon</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Dibayar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($payrollRows as $row)
                        <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="px-5 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $row['outlet_name'] }}</td>
                            <td class="px-5 py-3 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">{{ number_format($row['employee_count']) }}</td>
                            <td class="px-5 py-3 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['total_base']) }}</td>
                            <td class="px-5 py-3 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['total_bonus']) }}</td>
                            <td class="px-5 py-3 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">{{ $this->formatRupiah($row['total_kasbon_deduction']) }}</td>
                            <td class="px-5 py-3 text-right text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ $this->formatRupiah($row['total_paid']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                                Belum ada gaji berstatus "Dibayar" pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Section: Saldo Kasbon --}}
    <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-white/10">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Saldo Kasbon Berjalan</h3>
                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Snapshot saat ini (bukan dibatasi periode filter) — hanya karyawan aktif dengan saldo &gt; 0</p>
            </div>
            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-300">
                {{ $kasbonRows->count() }} karyawan
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-white/10">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Karyawan</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Outlet</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Saldo Kasbon</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($kasbonRows as $row)
                        <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="px-5 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $row['name'] }}</td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $row['outlet_name'] }}</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold tabular-nums text-amber-700 dark:bg-amber-400/10 dark:text-amber-400">
                                    {{ $this->formatRupiah($row['outstanding']) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                                Tidak ada karyawan dengan saldo kasbon berjalan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
