<form method="GET" class="flex flex-wrap items-end gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Dari tanggal</label>
        <input type="date" name="start_date" value="{{ $startDate }}"
            class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Sampai tanggal</label>
        <input type="date" name="end_date" value="{{ $endDate }}"
            class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
    </div>
    @if ($this->isAdminUser())
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Outlet</label>
            <select name="outlet_id"
                class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                <option value="">Semua outlet</option>
                @foreach ($this->outletOptions() as $id => $name)
                    <option value="{{ $id }}" @selected($outletId == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <button type="submit"
        class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600">
        Terapkan
    </button>
</form>
