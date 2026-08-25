@props(['summary' => []])

@if (filled($summary))
    <div {{ $attributes->merge(['class' => 'report-kpi-grid']) }}>
        @foreach ($summary as $item)
            <x-report.kpi-card :label="$item['label']" :value="$item['value']" />
        @endforeach
    </div>
@endif
