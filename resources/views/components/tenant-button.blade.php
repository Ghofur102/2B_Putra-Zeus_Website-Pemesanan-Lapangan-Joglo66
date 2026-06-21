@props([
    'variant' => 'primary',
    'type' => 'button',
    'href' => null,
    'disabled' => false
])

@php
    $isLink = $href !== null;
    $element = $isLink ? 'a' : 'button';

    $baseClasses = 'inline-flex items-center justify-center font-medium text-sm rounded-tenant-md shadow-tenant-sm transition-all select-none border border-transparent text-center';

    $variantClasses = [
        'primary' => 'bg-primary text-white hover:bg-primary-hover active:scale-[0.98]',
        'danger'  => 'bg-red-50 text-red-600 hover:bg-red-100/80 border-red-200 active:scale-[0.98]',
    ][$variant] ?? 'bg-primary text-white';

    $disabledClasses = $disabled
        ? 'opacity-60 cursor-not-allowed pointer-events-none shadow-none transform-none'
        : 'cursor-pointer';

    $attributes = $attributes->merge([
        'class' => "{$baseClasses} {$variantClasses} {$disabledClasses}"
    ]);

    if ($isLink) {
        $attributes = $attributes->merge(['href' => $disabled ? '#' : $href]);
    } else {
        $attributes = $attributes->merge(['type' => $type]);
        if ($disabled) {
            $attributes = $attributes->merge(['disabled' => true]);
        }
    }
@endphp

<{{ $element }} {{ $attributes }}>
    {{ $slot }}
</{{ $element }}>
