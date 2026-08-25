@props(['align' => 'left'])

<th data-align="{{ $align }}" {{ $attributes }}>
    {{ $slot }}
</th>
