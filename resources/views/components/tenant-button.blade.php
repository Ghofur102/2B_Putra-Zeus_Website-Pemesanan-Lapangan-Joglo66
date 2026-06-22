@props([
    'variant' => 'primary',
    'type' => 'button',
    'href' => null,
    'disabled' => false
])

@php
    $isLink = $href !== null;
    $element = $isLink ? 'a' : 'button';

    $baseClasses = 'inline-flex items-center justify-center font-medium text-sm rounded-tenant-md shadow-tenant-sm transition-all select-none border border-transparent text-center disabled:opacity-60 disabled:pointer-events-none disabled:shadow-none disabled:transform-none';

    $variantClasses = [
        'primary' => 'bg-primary text-white hover:bg-primary-hover active:scale-[0.98]',
        'danger'  => 'bg-red-50 text-red-600 hover:bg-red-100/80 border-red-200 active:scale-[0.98]',
    ][$variant] ?? 'bg-primary text-white';

    $cursorClasses = $isLink ? 'cursor-pointer' : 'cursor-pointer disabled:cursor-not-allowed';

    $attributes = $attributes->merge([
        'class' => "{$baseClasses} {$variantClasses} {$cursorClasses}"
    ]);

    if ($isLink) {
        if ($disabled) {
            $attributes = $attributes->merge([
                'href' => '#',
                'class' => $attributes->get('class') . ' opacity-60 pointer-events-none'
            ]);
        } else {
            $attributes = $attributes->merge(['href' => $href]);
        }
    } else {
        $attributes = $attributes->merge(['type' => $type]);
        if ($disabled) {
            $attributes = $attributes->merge(['disabled' => 'disabled']);
        }
    }
@endphp

<{{ $element }} {{ $attributes }}>
    {{ $slot }}
</{{ $element }}>
