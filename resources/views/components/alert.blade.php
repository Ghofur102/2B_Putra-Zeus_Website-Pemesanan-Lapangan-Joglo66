@if (session('success') || session('error') || session('warning') || session('info'))
    @php
        $type = session('success') ? 'success' : (session('error') ? 'error' : (session('warning') ? 'warning' : 'info'));

        $classes = [
            'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            'error'   => 'bg-rose-50 text-rose-800 border-rose-200',
            'warning' => 'bg-amber-50 text-amber-800 border-amber-200',
            'info'    => 'bg-blue-50 text-blue-800 border-blue-200',
        ][$type];

        $message = session('success') ?? session('error') ?? session('warning') ?? session('info');
    @endphp

    <div class="alert-dismissible flex items-center justify-between p-4 mb-4 border rounded-tenant-lg shadow-tenant-sm transition-all duration-30 {{ $classes }}">
        <div class="flex items-center gap-3 text-sm font-medium">
            <span>{{ $message }}</span>
        </div>
        <button type="button" class="alert-close-button text-gray-400 hover:text-gray-700 p-1 rounded-tenant-md cursor-pointer transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
@endif
