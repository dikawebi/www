@props(['label', 'value'])

<x-filament::section>
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
        {{ $label }}
    </p>
    <p class="mt-2 text-xl font-bold text-gray-950 dark:text-white">
        {{ $value }}
    </p>
</x-filament::section>
