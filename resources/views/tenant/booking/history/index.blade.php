@extends('tenant.layouts.app')

@section('title', 'Riwayat Transaksi')

@section('content')
<div class="w-full px-4 md:px-6 lg:px-16 pb-8">

    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Riwayat Transaksi</h1>
            <p class="text-gray-500 text-sm mt-1">Lacak semua pemesanan dan status tagihan Anda di sini.</p>
        </div>
    </div>

    <x-tenant-card variant="flat" class="mb-8 overflow-visible">
        <div class="flex flex-col md:flex-row gap-4 items-end">

            <div class="flex-1 w-full">
                <label for="filterSearch" class="block text-gray-600 text-xs font-medium mb-2 uppercase tracking-wider">Pencarian Transaksi</label>
                <div class="flex items-center w-full h-12 bg-gray-50 border border-gray-200 rounded-tenant-md focus-within:bg-white focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-all px-4 overflow-hidden">
                    <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" id="filterSearch" placeholder="Ketik Nama Tim atau No. Referensi Duitku..."
                        class="w-full h-full bg-transparent text-sm text-gray-800 placeholder-gray-400 outline-none ml-3">
                </div>
            </div>

            <div class="w-full md:w-auto relative">
                <button type="button" id="toggleFilterBtn" class="w-full md:w-auto flex justify-center items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 font-medium px-6 h-12 rounded-tenant-md transition-all text-sm shadow-tenant-sm cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter Lanjutan
                </button>

                <div id="filterPopup" class="hidden absolute right-0 top-full mt-2 w-72 sm:w-80 bg-white rounded-tenant-lg shadow-tenant-md border border-gray-100 p-6 z-30">
                    <div class="flex justify-between items-center border-b border-gray-100 pb-3 mb-4">
                        <h3 class="font-bold text-gray-800 text-sm">Saring Berdasarkan</h3>
                        <button type="button" id="closeFilterBtn" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex flex-col gap-4">
                        <div>
                            <label for="filterStartDate" class="block text-gray-600 text-xs font-medium mb-2 uppercase tracking-wider">Mulai Tanggal</label>
                            <input type="date" id="filterStartDate" class="w-full h-12 px-4 bg-gray-50 border border-gray-200 rounded-tenant-md text-sm text-gray-800 focus:bg-white focus:border-primary outline-none transition-all cursor-pointer">
                        </div>

                        <div>
                            <label for="filterEndDate" class="block text-gray-600 text-xs font-medium mb-2 uppercase tracking-wider">Sampai Tanggal</label>
                            <input type="date" id="filterEndDate" class="w-full h-12 px-4 bg-gray-50 border border-gray-200 rounded-tenant-md text-sm text-gray-800 focus:bg-white focus:border-primary outline-none transition-all cursor-pointer">
                        </div>

                        <div>
                            <label for="filterStatus" class="block text-gray-600 text-xs font-medium mb-2 uppercase tracking-wider">Status Pembayaran</label>
                            <select id="filterStatus" class="w-full h-12 px-4 bg-gray-50 border border-gray-200 rounded-tenant-md text-sm text-gray-800 focus:bg-white focus:border-primary outline-none transition-all cursor-pointer">
                                <option value="">Semua Status</option>
                                @foreach ($availableStatuses as $status)
                                    <option value="{{ strtolower($status) }}">{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="w-full mt-2 pt-4 border-t border-gray-100">
                            <x-tenant-button id="btnReset" variant="danger" class="w-full py-3">
                                Reset Filter
                            </x-tenant-button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-tenant-card>

    <div class="flex flex-col gap-3" id="transactionContainer">
        @forelse($transactions as $trx)
            <div class="transaction-card bg-white rounded-tenant-lg p-4 md:p-5 shadow-tenant-sm border border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:shadow-tenant-md transition-all"
                data-search="{{ strtolower($trx->team_name . ' ' . ($trx->mainPayment->reference_id ?? 'No Ref')) }}"
                data-date="{{ \Carbon\Carbon::parse($trx->booking_date)->format('Y-m-d') }}"
                data-status="{{ $trx->overallStatus }}">

                <div class="flex-1 min-w-48">
                    <div class="flex items-center gap-3 mb-1">
                        <h3 class="font-bold text-gray-900 text-base md:text-lg line-clamp-1">{{ $trx->team_name }}</h3>
                        <span class="px-2.5 py-0.5 rounded-tenant-sm text-xs font-bold {{ $trx->badgeClass }} uppercase tracking-wider shrink-0">
                            {{ $trx->overallStatus }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500">
                        <span class="font-mono text-gray-600 text-xs">{{ $trx->mainPayment->reference_id ?? 'No Ref' }}</span>
                        <span class="hidden sm:inline-block text-gray-300">•</span>
                        <span>{{ \Carbon\Carbon::parse($trx->booking_date)->format('d M Y') }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-6 md:gap-8 text-sm md:border-l md:border-r border-gray-100 md:px-6">
                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Tagihan Aktif</span>
                        <span class="font-semibold text-gray-800">Rp {{ number_format($trx->tagihanAktif, 0, ',', '.') }}</span>
                    </div>

                    @if($trx->uangRefund > 0)
                        <div class="flex flex-col">
                            <span class="text-xs font-bold text-orange-400 uppercase tracking-widest mb-1">Di-Refund</span>
                            <span class="font-bold text-orange-500">Rp {{ number_format($trx->uangRefund, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Sisa Tagihan</span>
                        @if($trx->overallStatus === 'cancelled' && $trx->sisaTagihan == 0)
                            <span class="font-bold text-gray-400">-</span>
                        @else
                            <span class="font-bold {{ $trx->sisaTagihan > 0 ? 'text-red-500' : 'text-green-600' }}">
                                Rp {{ number_format($trx->sisaTagihan, 0, ',', '.') }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="shrink-0 w-full md:w-auto mt-2 md:mt-0">
                    <x-tenant-button :href="route('tenant.booking.history.show', $trx->id)" variant="primary" class="w-full md:w-auto py-2.5 px-5 gap-1">
                        Detail <span aria-hidden="true">&rarr;</span>
                    </x-tenant-button>
                </div>
            </div>
        @empty
            <div id="defaultEmptyState" class="w-full bg-white rounded-tenant-lg border border-gray-200 py-16 text-center shadow-tenant-sm">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-gray-500 font-medium text-sm">Belum ada transaksi.</p>
            </div>
        @endforelse

        <div id="jsEmptyState" class="w-full bg-white rounded-tenant-lg border border-gray-200 py-16 text-center shadow-tenant-sm" style="display: none;">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <p class="text-gray-500 font-medium text-sm">Pencarian tidak menemukan hasil yang cocok.</p>
        </div>
    </div>

    @if ($transactions->hasPages())
        <div class="mt-8">
            {{ $transactions->links() }}
        </div>
    @endif

</div>
@endsection
