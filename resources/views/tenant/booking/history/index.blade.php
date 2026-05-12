@extends('tenant.layouts.app')

@section('title', 'Riwayat Transaksi')

@section('content')
    <div class="w-full py-5">

        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Riwayat Transaksi</h1>
                <p class="text-gray-500 text-sm mt-1">Lacak semua pemesanan dan status pembayaran Anda di sini.</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 mb-8 relative">
            <div class="flex flex-col md:flex-row gap-3 items-end">

                <div class="flex-1 w-full">
                    <label class="block text-gray-600 text-xs font-medium mb-2 uppercase tracking-wider">Pencarian
                        Transaksi</label>
                    <div class="relative">
                        <input type="text" id="filterSearch" placeholder="Ketik Nama Tim atau No. Referensi Duitku..."
                            class="block w-full py-3 px-8 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                    </div>

                </div>

                <div class="md:w-auto relative">
                    <button type="button" id="toggleFilterBtn"
                        class="w-full md:w-auto flex justify-center items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 font-medium py-3 px-6 rounded-xl transition-all text-sm shadow-sm h-11.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                            </path>
                        </svg>
                    </button>

                    <div id="filterPopup"
                        class="hidden absolute right-0 top-[calc(100%+12px)] w-75 sm:w-85 bg-white rounded-2xl shadow-xl border border-gray-100 p-6 z-30">
                        <div class="flex justify-between items-center border-b border-gray-100 pb-3 mb-4">
                            <h3 class="font-bold text-gray-800">Saring Berdasarkan</h3>
                            <button type="button" id="closeFilterBtn" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="flex flex-col gap-4">
                            <div class="w-full">
                                <label class="block text-gray-600 text-xs font-medium mb-2 uppercase tracking-wider">Mulai
                                    Tanggal</label>
                                <input type="date" id="filterStartDate"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-800 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all cursor-pointer">
                            </div>

                            <div class="w-full">
                                <label class="block text-gray-600 text-xs font-medium mb-2 uppercase tracking-wider">Sampai
                                    Tanggal</label>
                                <input type="date" id="filterEndDate"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-800 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all cursor-pointer">
                            </div>

                            <div class="w-full">
                                <label class="block text-gray-600 text-xs font-medium mb-2 uppercase tracking-wider">Status
                                    Pembayaran</label>
                                <select id="filterStatus"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-800 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all cursor-pointer">
                                    <option value="">Semua Status</option>
                                    @foreach ($availableStatuses as $status)
                                        <option value="{{ strtolower($status) }}">{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="w-full mt-2 pt-4 border-t border-gray-100">
                                <button type="button" id="btnReset"
                                    class="w-full bg-red-50 hover:bg-red-100 text-red-600 font-medium py-2.5 px-6 rounded-xl transition-all text-sm border border-red-100">
                                    Reset Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-200 text-gray-500 text-xs uppercase tracking-wider">
                            <th class="px-6 py-4 font-medium">Tanggal Booking</th>
                            <th class="px-6 py-4 font-medium">Tim & No. Ref</th>
                            <th class="px-6 py-4 font-medium">Tipe Bayar</th>
                            <th class="px-6 py-4 font-medium">Total</th>
                            <th class="px-6 py-4 font-medium">Status</th>
                            <th class="px-6 py-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="transactionTableBody" class="text-sm divide-y divide-gray-100">
                        @forelse($transactions as $trx)
                            @php
                                $latestPayment = $trx->payments->sortByDesc('created_at')->first();
                                $status = strtolower($latestPayment->status ?? 'unknown');

                                $badgeClass = 'bg-gray-100 text-gray-600';
                                if ($status === 'success') {
                                    $badgeClass = 'bg-green-50 text-green-600 border border-green-200';
                                } elseif ($status === 'pending') {
                                    $badgeClass = 'bg-yellow-50 text-yellow-600 border border-yellow-200';
                                } elseif ($status === 'failed' || $status === 'expired') {
                                    $badgeClass = 'bg-red-50 text-red-600 border border-red-200';
                                }

                                // Siapkan variabel untuk memudahkan filter JS membaca baris ini
                                $rawDate = \Carbon\Carbon::parse($trx->booking_date)->format('Y-m-d');
                                $rawSearchData = strtolower(
                                    $trx->team_name . ' ' . ($latestPayment->reference_id ?? ''),
                                );
                            @endphp

                            <tr class="transaction-row hover:bg-gray-50/50 transition-colors"
                                data-search="{{ $rawSearchData }}" data-date="{{ $rawDate }}"
                                data-status="{{ $status }}">

                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-800">
                                        {{ \Carbon\Carbon::parse($trx->booking_date)->format('d M Y') }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $trx->created_at->format('H:i') }} WIB
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-800">{{ $trx->team_name }}</div>
                                    <div class="font-mono text-xs text-gray-500 mt-0.5">
                                        {{ $latestPayment->reference_id ?? 'No Ref' }}</div>
                                </td>

                                <td class="px-6 py-4 text-gray-600 capitalize">
                                    {{ $latestPayment->payment_type ?? '-' }}
                                </td>

                                <td class="px-6 py-4 font-semibold text-gray-800">
                                    Rp {{ number_format($latestPayment->amount ?? 0, 0, ',', '.') }}
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-semibold {{ $badgeClass }} capitalize inline-block">
                                        {{ $status }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('tenant.booking.history.show', $trx->id) }}"
                                        class="text-primary hover:text-blue-800 font-medium text-sm transition-colors inline-flex items-center gap-1">
                                        Detail <span aria-hidden="true">&rarr;</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr id="defaultEmptyState">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    Tidak ada transaksi yang ditemukan di database.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tbody id="jsEmptyState" style="display: none;">
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                Pencarian tidak menemukan hasil yang cocok.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            @if ($transactions->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/30">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tangkap semua elemen input filter
            const searchInput = document.getElementById('filterSearch');
            const startDateInput = document.getElementById('filterStartDate');
            const endDateInput = document.getElementById('filterEndDate');
            const statusSelect = document.getElementById('filterStatus');
            const btnReset = document.getElementById('btnReset');

            // Tangkap baris tabel
            const rows = document.querySelectorAll('.transaction-row');
            const jsEmptyState = document.getElementById('jsEmptyState');
            const defaultEmptyState = document.getElementById('defaultEmptyState');

            // Fungsi Utama Filtering
            function filterTable() {
                const searchValue = searchInput.value.toLowerCase();
                const startDateValue = startDateInput.value;
                const endDateValue = endDateInput.value;
                const statusValue = statusSelect.value;

                let visibleRowCount = 0;

                rows.forEach(row => {
                    const rowSearch = row.getAttribute('data-search');
                    const rowDate = row.getAttribute('data-date');
                    const rowStatus = row.getAttribute('data-status');

                    const matchSearch = rowSearch.includes(searchValue);
                    const matchStatus = statusValue === '' || rowStatus === statusValue;

                    let matchDate = true;
                    if (startDateValue && rowDate < startDateValue) matchDate = false;
                    if (endDateValue && rowDate > endDateValue) matchDate = false;

                    if (matchSearch && matchStatus && matchDate) {
                        row.style.display = '';
                        visibleRowCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (defaultEmptyState) defaultEmptyState.style.display = 'none';
                jsEmptyState.style.display = visibleRowCount === 0 && rows.length > 0 ? '' : 'none';
            }

            searchInput.addEventListener('input', filterTable);
            startDateInput.addEventListener('change', filterTable);
            endDateInput.addEventListener('change', filterTable);
            statusSelect.addEventListener('change', filterTable);

            btnReset.addEventListener('click', function() {
                searchInput.value = '';
                startDateInput.value = '';
                endDateInput.value = '';
                statusSelect.value = '';
                filterTable();
            });

            const toggleFilterBtn = document.getElementById('toggleFilterBtn');
            const closeFilterBtn = document.getElementById('closeFilterBtn');
            const filterPopup = document.getElementById('filterPopup');

            toggleFilterBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                filterPopup.classList.toggle('hidden');
            });

            closeFilterBtn.addEventListener('click', function() {
                filterPopup.classList.add('hidden');
            });

            document.addEventListener('click', function(e) {
                if (!filterPopup.contains(e.target) && e.target !== toggleFilterBtn) {
                    filterPopup.classList.add('hidden');
                }
            });
        });
    </script>
@endpush
