<div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
    <form method="GET" class="p-4 sm:p-5">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-[repeat(3,minmax(0,1fr))_auto]">
            <div>
                <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Dari Tanggal
                </label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-white/10 dark:bg-gray-800 dark:text-white" />
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Sampai Tanggal
                </label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-white/10 dark:bg-gray-800 dark:text-white" />
            </div>

            @if ($this->isAdminUser())
                <div>
                    <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Outlet
                    </label>
                    <select name="outlet_id"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-white/10 dark:bg-gray-800 dark:text-white">
                        <option value="">Semua Outlet</option>
                        @foreach ($this->outletOptions() as $id => $name)
                            <option value="{{ $id }}" @selected($outletId == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="flex items-end">
                <button type="submit"
                    class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600 lg:w-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v2.586a1 1 0 01-.293.707l-5.414 5.414a1 1 0 00-.293.707V17l-4-2v-4.586a1 1 0 00-.293-.707L3.293 5.293A1 1 0 013 4.586V3z" clip-rule="evenodd" />
                    </svg>
                    Terapkan Filter
                </button>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-4 dark:border-white/10">
            <span class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Cepat:</span>
            @foreach ($this->quickRanges() as $range)
                @php [$rangeStart, $rangeEnd, $rangeLabel] = $range; @endphp
                <a href="{{ request()->fullUrlWithQuery(['start_date' => $rangeStart, 'end_date' => $rangeEnd]) }}"
                    class="rounded-full px-3 py-1 text-xs font-medium transition
                        {{ $this->isActiveQuickRange($rangeStart, $rangeEnd)
                            ? 'bg-amber-500 text-white'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10' }}">
                    {{ $rangeLabel }}
                </a>
            @endforeach
        </div>
    </form>
</div>
