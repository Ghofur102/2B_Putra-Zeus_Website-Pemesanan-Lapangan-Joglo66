@props([
    'variant' => 'default'
])

@php
    $baseClasses = 'bg-white border border-gray-200 rounded-tenant-lg shadow-tenant-sm';

    $variantClasses = [
        'default' => 'p-6',
        'flat'    => 'p-4',
        'accent'  => 'p-4 border-l-4 border-l-primary'
    ][$variant] ?? 'p-6';
@endphp

<div {{ $attributes->merge(['class' => "{$baseClasses} {$variantClasses}"]) }}>
    {{ $slot }}
</div>
