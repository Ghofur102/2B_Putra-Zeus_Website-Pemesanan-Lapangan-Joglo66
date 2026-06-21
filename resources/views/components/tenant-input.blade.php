@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => '',
    'required' => false
])

@php
    $hasError = $errors->has($name);
    $isPassword = $type === 'password';

    $baseClasses = 'w-full px-4 py-3 bg-gray-50 border rounded-tenant-md text-gray-800 placeholder-gray-400 focus:bg-white focus:ring-1 transition-all outline-none text-sm';

    if ($isPassword || $slot->isNotEmpty()) {
        $baseClasses .= ' pr-12';
    }

    $statusClasses = $hasError
        ? 'border-red-300 focus:border-red-500 focus:ring-red-500 text-red-900'
        : 'border-gray-200 focus:border-primary focus:ring-primary';
@endphp

<div class="w-full">
    @if($label)
        <label for="{{ $name }}" class="block text-gray-700 text-sm font-medium mb-2">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <input
            type="{{ $type }}"
            id="{{ $name }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge(['class' => $baseClasses . ' ' . $statusClasses]) }}
        >

        @if($isPassword)
            <button type="button" class="toggle-password absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-primary transition-colors cursor-pointer" data-target="{{ $name }}">
                <svg class="w-5 h-5 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </button>
        @elseif($slot->isNotEmpty())
            <div class="absolute inset-y-0 right-0 flex items-center pr-4">
                {{ $slot }}
            </div>
        @endif
    </div>

    @error($name)
        <p class="mt-2 text-xs font-semibold text-red-600">
            ⚠️ {{ $message }}
        </p>
    @enderror
</div>
