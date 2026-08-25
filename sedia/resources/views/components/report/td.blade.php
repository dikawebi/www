@props([
    'align' => 'left',
    'strong' => false,
    'tone' => 'default',
])

@php
    $colorClasses = match ($tone) {
        'danger'  => 'text-danger-600 dark:text-danger-400',
        'success' => 'text-success-600 dark:text-success-400',
        default   => ($strong ? 'font-semibold text-gray-950 dark:text-white' : 'text-gray-700 dark:text-gray-300'),
    };
@endphp

<td data-align="{{ $align }}" {{ $attributes->merge(['class' => $colorClasses]) }}>
    {{ $slot }}
</td>
