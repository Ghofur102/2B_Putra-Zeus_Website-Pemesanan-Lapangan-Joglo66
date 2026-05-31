@extends('tenant.layouts.app')

@section('title', 'Riwayat Transaksi')

@section('content')
<div class="w-full px-4 md:px-6 lg:px-15 pb-8">

    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Riwayat Transaksi</h1>
            <p class="text-gray-500 text-sm mt-1">Lacak semua pemesanan dan status tagihan Anda di sini.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-200 mb-8 relative">
        <div class="flex flex-col md:flex-row gap-4 items-end">

            <div class="flex-1 w-full">
                <label for="filterSearch" class="block text-gray-600 text-xs font-medium mb-2 uppercase tracking-wider">Pencarian Transaksi</label>
                <div class="flex items-center w-full h-11.5 bg-gray-50 border border-gray-200 rounded-xl focus-within:bg-white focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-all px-4 overflow-hidden">
                    <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" id="filterSearch" placeholder="Ketik Nama Tim atau No. Referensi Duitku..."
                        class="w-full h-full bg-transparent text-sm text-gray-800 placeholder-gray-400 outline-none ml-3">
                </div>
            </div>

            <div class="w-full md:w-auto relative">
                <button type="button" id="toggleFilterBtn" class="w-full md:w-auto flex justify-center items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 font-medium px-6 h-11.5 rounded-xl transition-all text-sm shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    Filter Lanjutan
                </button>

                <div id="filterPopup" class="hidden absolute right-0 top-[calc(100%+8px)] w-75 sm:w-85 bg-white rounded-2xl shadow-xl border border-gray-100 p-6 z-30">
                    <div class="flex justify-between items-center border-b border-gray-100 pb-3 mb-4">
                        <h3 class="font-bold text-gray-800">Saring Berdasarkan</h3>
                        <button type="button" id="closeFilterBtn" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="flex flex-col gap-4">
                        <div class="w-full">
                            <label for="filterStartDate" class="block text-gray-600 text-xs font-medium mb-2 uppercase tracking-wider">Mulai Tanggal</label>
                            <input type="date" id="filterStartDate" class="w-full h-11.5 px-4 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-800 focus:bg-white focus:border-primary outline-none transition-all cursor-pointer">
                        </div>

                        <div class="w-full">
                            <label for="filterEndDate" class="block text-gray-600 text-xs font-medium mb-2 uppercase tracking-wider">Sampai Tanggal</label>
                            <input type="date" id="filterEndDate" class="w-full h-11.5 px-4 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-800 focus:bg-white focus:border-primary outline-none transition-all cursor-pointer">
                        </div>

                        <div class="w-full">
                            <label for="filterStatus" class="block text-gray-600 text-xs font-medium mb-2 uppercase tracking-wider">Status Pembayaran</label>
                            <select id="filterStatus" class="w-full h-11.5 px-4 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-800 focus:bg-white focus:border-primary outline-none transition-all cursor-pointer">
                                <option value="">Semua Status</option>
                                @foreach ($availableStatuses as $status)
                                    <option value="{{ strtolower($status) }}">{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="w-full mt-2 pt-4 border-t border-gray-100">
                            <button type="button" id="btnReset" class="w-full h-11.5 bg-red-50 hover:bg-red-100 text-red-600 font-medium px-6 rounded-xl transition-all text-sm border border-red-100">
                                Reset Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-3" id="transactionContainer">

        @forelse($transactions as $trx)
            <div class="transaction-card bg-white rounded-xl p-4 md:p-5 shadow-sm border border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:shadow-md transition-all"
                data-search="{{ strtolower($trx->team_name . ' ' . ($trx->mainPayment->reference_id ?? 'No Ref')) }}"
                data-date="{{ \Carbon\Carbon::parse($trx->booking_date)->format('Y-m-d') }}"
                data-status="{{ $trx->overallStatus }}">

                <div class="flex-1 min-w-50">
                    <div class="flex items-center gap-3 mb-1">
                        <h3 class="font-bold text-gray-800 text-base md:text-lg line-clamp-1">{{ $trx->team_name }}</h3>
                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold {{ $trx->badgeClass }} uppercase tracking-wider shrink-0">
                            {{ $trx->overallStatus }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500">
                        <span class="font-mono text-gray-600">{{ $trx->mainPayment->reference_id ?? 'No Ref' }}</span>
                        <span class="hidden sm:inline-block text-gray-300">•</span>
                        <span>{{ \Carbon\Carbon::parse($trx->booking_date)->format('d M Y') }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-5 md:gap-8 text-sm md:border-l md:border-r border-gray-100 md:px-6">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Tagihan Aktif</span>
                        <span class="font-semibold text-gray-800">Rp {{ number_format($trx->tagihanAktif, 0, ',', '.') }}</span>
                    </div>

                    @if($trx->uangRefund > 0)
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-orange-400 uppercase tracking-widest mb-0.5">Di-Refund</span>
                        <span class="font-bold text-orange-500">Rp {{ number_format($trx->uangRefund, 0, ',', '.') }}</span>
                    </div>
                    @endif

                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Sisa Tagihan</span>
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
                    <a href="{{ route('tenant.booking.history.show', $trx->id) }}" class="w-full md:w-auto inline-flex justify-center items-center bg-gray-50 hover:bg-primary hover:text-white text-gray-700 font-medium py-2.5 px-5 rounded-lg transition-colors text-sm border border-gray-200 hover:border-primary">
                        Detail <span aria-hidden="true" class="ml-1">&rarr;</span>
                    </a>
                </div>
            </div>

        @empty
            <div id="defaultEmptyState" class="w-full bg-white rounded-2xl border border-gray-200 py-16 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <p class="text-gray-500 font-medium">Belum ada transaksi.</p>
            </div>
        @endforelse

        <div id="jsEmptyState" class="w-full bg-white rounded-2xl border border-gray-200 py-16 text-center" style="display: none;">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <p class="text-gray-500 font-medium">Pencarian tidak menemukan hasil yang cocok.</p>
        </div>
    </div>

    @if ($transactions->hasPages())
        <div class="mt-8">
            {{ $transactions->links() }}
        </div>
    @endif

</div>
@endsection

@push('scripts')
    @vite('resources/js/list-transaction.js')
@endpush
