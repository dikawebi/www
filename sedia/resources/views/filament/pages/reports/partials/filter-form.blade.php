<div class="report-print-header">
    <p class="report-print-header-title">{{ config('app.name') }} — {{ $this->getTitle() }}</p>
    <p class="report-print-header-meta">
        Outlet: {{ $this->currentOutletName() }}
        &middot; Periode: {{ $this->periodLabel() }}
        &middot; Dicetak: {{ now()->translatedFormat('d M Y H:i') }}
    </p>
</div>

<x-filament::section class="report-no-print">
    <form method="GET">
        <div class="report-filter-grid">
            {{-- Start Date --}}
            <div class="space-y-2">
                <label for="start_date" class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                    Dari Tanggal
                </label>
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="date"
                        name="start_date"
                        id="start_date"
                        value="{{ $startDate }}"
                    />
                </x-filament::input.wrapper>
            </div>

            {{-- End Date --}}
            <div class="space-y-2">
                <label for="end_date" class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                    Sampai Tanggal
                </label>
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="date"
                        name="end_date"
                        id="end_date"
                        value="{{ $endDate }}"
                    />
                </x-filament::input.wrapper>
            </div>

            {{-- Outlet (Admin Only) --}}
            @if ($this->isAdminUser())
                <div class="space-y-2">
                    <label for="outlet_id" class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                        Outlet
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select name="outlet_id" id="outlet_id">
                            <option value="">Semua Outlet</option>
                            @foreach ($this->outletOptions() as $id => $name)
                                <option value="{{ $id }}" @selected($outletId == $id)>{{ $name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            @endif
        </div>

        <div class="report-filter-actions">
            <div class="report-filter-actions-buttons">
                <x-filament::button type="submit" icon="heroicon-m-funnel">
                    Terapkan Filter
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="gray"
                    icon="heroicon-m-printer"
                    onclick="window.sediaPrintReport()"
                >
                    Cetak
                </x-filament::button>
            </div>

            {{-- Quick Range Shortcuts --}}
            <div class="flex flex-wrap items-center gap-2">
                @foreach ($this->quickRanges() as $key => $range)
                    @php [$rangeStart, $rangeEnd, $rangeLabel] = $range; @endphp
                    <x-filament::badge
                        :color="$this->isActiveQuickRange($rangeStart, $rangeEnd) ? 'primary' : 'gray'"
                        tag="a"
                        href="{{ request()->fullUrlWithQuery(['start_date' => $rangeStart, 'end_date' => $rangeEnd]) }}"
                        class="cursor-pointer"
                    >
                        {{ $rangeLabel }}
                    </x-filament::badge>
                @endforeach
            </div>
        </div>
    </form>
</x-filament::section>

@include('filament.pages.reports.partials.print-styles')
