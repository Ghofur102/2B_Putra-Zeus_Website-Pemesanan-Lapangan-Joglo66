<div class="bg-gray-100 rounded-2xl p-6 border border-gray-200 shadow-sm flex flex-col h-full">
    <div class="flex flex-col items-center justify-center mb-6 border-b border-gray-300 pb-4">
        <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mb-2 text-gray-700">
            {!! $icon !!}
        </div>
        <h3 class="text-base font-bold text-gray-800 uppercase tracking-wide">{{ $title }}</h3>
    </div>
    <div class="flex-1 flex flex-col gap-4">
        {{ $slot }}
    </div>
</div>
