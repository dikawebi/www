@props([
    'heading' => null,
    'description' => null,
    'footer' => null,
])

<x-filament::section {{ $attributes->merge(['class' => 'mt-6']) }}>
    @if (filled($heading))
        <x-slot name="heading">{{ $heading }}</x-slot>
    @endif

    @if (filled($description))
        <x-slot name="description">{{ $description }}</x-slot>
    @endif

    <div class="report-table-wrapper">
        <table class="report-table">
            <thead>
                {{ $head }}
            </thead>
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if (filled($footer))
        <x-slot name="footer">
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $footer }}</p>
        </x-slot>
    @endif
</x-filament::section>
