@php
    $isDone = str_contains(strtolower($statusText), 'done');
    $accentColor = $isDone ? 'border-gray-300' : 'border-[#3a5a8c]';
    $badgeClass = $isDone ? 'bg-gray-100 text-gray-600' : 'bg-blue-50 text-[#3a5a8c]';
@endphp

<div class="flex flex-col md:flex-row items-start md:items-center justify-between bg-white rounded-xl p-4 mb-3 shadow-sm border border-gray-200 border-l-2 {{ $accentColor }} hover:shadow transition-shadow gap-4">

    <div class="flex flex-row items-center gap-4 md:gap-5 w-full md:w-auto overflow-hidden">

        <div class="flex flex-col items-center justify-center bg-gray-50 rounded-lg px-3 py-2 border border-gray-200 min-w-[70px] shrink-0">
            <span class="text-xs font-semibold text-gray-500 uppercase">{{ $dayMonth }}</span>
            <strong class="text-lg font-black text-gray-800 leading-none mt-1">{{ $year }}</strong>
        </div>

        <div class="flex flex-col gap-1 overflow-hidden">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="text-base font-bold text-gray-800">{{ $time }}</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span class="text-sm text-gray-600 truncate">Tim: <span class="font-semibold text-gray-900">{{ $teamName }}</span></span>
            </div>
        </div>
    </div>

    <div class="w-full md:w-auto mt-2 md:mt-0 text-left md:text-right shrink-0">
        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold {{ $badgeClass }} whitespace-nowrap">
            @if($isDone)
                <svg class="w-3.5 h-3.5 mr-1.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
            @else
                <svg class="w-3.5 h-3.5 mr-1.5 shrink-0 animate-pulse" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>
            @endif
            {{ $statusText }}
        </span>
    </div>
</div>
