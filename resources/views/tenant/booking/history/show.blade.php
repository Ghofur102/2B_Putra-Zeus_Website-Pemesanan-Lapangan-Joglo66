@extends('tenant.layouts.app')
@section('title', 'Detail Transaksi')

@php
    $latestPayment = $booking->payments->sortByDesc('created_at')->first();
    $overallStatus = strtolower($latestPayment->status ?? 'unknown');

    // Logika Sinkronisasi Harga Keseluruhan
    $totalHarga = $booking->details->sum('price') + $booking->attributes->sum('total');
    $sudahDibayar = $booking->payments->where('status', 'success')->sum('amount');
    $sisaTagihan = max(0, $totalHarga - $sudahDibayar);

    $statusColors = [
        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
        'failed'  => 'bg-rose-50 text-rose-700 border-rose-200',
        'expired' => 'bg-rose-50 text-rose-700 border-rose-200',
        'booked'  => 'bg-blue-50 text-blue-700 border-blue-200',
    ];
    $badgeClass = $statusColors[$overallStatus] ?? 'bg-gray-50 text-gray-700 border-gray-200';
@endphp

@section('content')
<div class="max-w-5xl mx-auto px-4 md:px-6 lg:px-8 pb-8 md:pb-12 mt-4 md:mt-0">

    <div class="bg-white rounded-3xl md:rounded-4xl shadow-sm border border-gray-200 overflow-hidden">

        <div class="bg-slate-50 border-b border-gray-100 p-5 sm:p-8 md:p-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 md:w-6 md:h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Detail Pemesanan
                </h1>
                <p class="text-gray-500 text-xs md:text-sm mt-1.5 md:mt-2 font-mono">
                    No. Ref: <span class="font-bold text-gray-800">{{ $latestPayment->reference_id ?? 'Menunggu Pembayaran' }}</span>
                </p>
            </div>

            <div class="flex flex-col items-start md:items-end gap-1.5 md:gap-2">
                <span class="px-3 py-1 md:px-4 md:py-1.5 rounded-full text-[10px] md:text-xs font-bold border {{ $badgeClass }} uppercase tracking-wider">
                    Status: {{ $overallStatus }}
                </span>
                <p class="text-gray-400 text-[10px] md:text-xs">Dibuat: {{ $booking->created_at->format('d M Y, H:i') }} WIB</p>
            </div>
        </div>

        <div class="p-5 sm:p-8 md:p-10">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-8 md:mb-10">

                <div class="bg-gray-50/50 rounded-2xl p-4 md:p-6 border border-gray-100 flex flex-col h-full">
                    <h3 class="text-[10px] md:text-xs font-bold text-gray-400 uppercase tracking-widest mb-3 md:mb-5">Informasi Pemesan</h3>
                    <div class="space-y-3 md:space-y-4 text-xs md:text-sm flex-1">
                        <div class="flex justify-between gap-4"><span class="text-gray-500 shrink-0">Nama Tim</span><span class="font-semibold text-gray-900 text-right">{{ $booking->team_name }}</span></div>
                        <div class="flex justify-between gap-4"><span class="text-gray-500 shrink-0">No. HP</span><span class="font-medium text-gray-900 text-right">{{ $booking->customer_phone ?? '-' }}</span></div>
                        <div class="flex justify-between gap-4"><span class="text-gray-500 shrink-0">Email</span><span class="font-medium text-gray-900 text-right break-all">{{ $booking->customer_email ?? '-' }}</span></div>
                    </div>
                </div>

                <div class="bg-gray-50/50 rounded-2xl p-4 md:p-6 border border-gray-100 flex flex-col h-full">
                    <h3 class="text-[10px] md:text-xs font-bold text-gray-400 uppercase tracking-widest mb-3 md:mb-5">Informasi Lapangan</h3>
                    <div class="space-y-3 md:space-y-4 text-xs md:text-sm flex-1">
                        <div class="flex justify-between gap-4"><span class="text-gray-500 shrink-0">Lapangan</span><span class="font-semibold text-gray-900 text-right">{{ $booking->field->name ?? '-' }}</span></div>
                        <div class="flex justify-between gap-4"><span class="text-gray-500 shrink-0">Tgl Induk</span><span class="font-medium text-gray-900 text-right">{{ \Carbon\Carbon::parse($booking->booking_date)->format('d F Y') }}</span></div>
                    </div>

                    <div class="mt-4 md:mt-5 pt-3 md:pt-4 border-t border-gray-200/70">
                        <span class="block text-gray-400 text-[10px] md:text-[11px] font-bold uppercase tracking-wider mb-1.5 md:mb-2">Catatan Tambahan:</span>
                        <div class="text-xs md:text-sm font-medium text-gray-800 bg-white p-2.5 md:p-3 rounded-lg border border-gray-100 wrap-break-word whitespace-pre-wrap min-h-10">{{ $booking->notes ?: 'Tidak ada catatan khusus dari penyewa.' }}</div>
                    </div>
                </div>
            </div>

            <h3 class="text-sm md:text-base font-bold text-gray-900 mb-3 md:mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Rincian Sesi Disewa
            </h3>

            <div class="overflow-x-auto mb-8 md:mb-10 border border-gray-200 rounded-xl md:rounded-2xl shadow-sm">
                <table class="w-full text-left text-xs md:text-sm whitespace-nowrap min-w-125">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr class="text-gray-500 text-[10px] md:text-xs uppercase tracking-wider">
                            <th class="px-4 py-3 md:px-6 md:py-4 font-semibold">Tanggal Main</th>
                            <th class="px-4 py-3 md:px-6 md:py-4 font-semibold">Sesi Jam</th>
                            <th class="px-4 py-3 md:px-6 md:py-4 font-semibold">Status</th>
                            <th class="px-4 py-3 md:px-6 md:py-4 font-semibold text-right">Harga Sesi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($booking->details as $detail)
                            @php
                                $detailPayment = $detail->payment->sortByDesc('created_at')->first();
                                $detailStatus = strtolower($detail->status ?? $detailPayment->status ?? 'pending');
                                $detailBadge = $statusColors[$detailStatus] ?? 'bg-gray-50 text-gray-700 border-gray-200';
                            @endphp
                            <tr class="hover:bg-gray-50/80 transition-colors">
                                <td class="px-4 py-3 md:px-6 md:py-4 text-gray-900">{{ \Carbon\Carbon::parse($detail->play_date)->format('d M Y') }}</td>
                                <td class="px-4 py-3 md:px-6 md:py-4 text-gray-900 font-medium">
                                    {{ \Carbon\Carbon::parse($detail->start_play_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($detail->end_play_time)->format('H:i') }}
                                </td>
                                <td class="px-4 py-3 md:px-6 md:py-4">
                                    <span class="px-2.5 py-1 md:px-3 md:py-1 rounded-full text-[10px] md:text-xs font-semibold border {{ $detailBadge }} capitalize">
                                        {{ $detailStatus }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 md:px-6 md:py-4 font-semibold text-gray-900 text-right">
                                    Rp {{ number_format($detail->price, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="w-full text-xs md:text-sm bg-gray-50/50 p-5 md:p-8 rounded-xl md:rounded-2xl border border-gray-100 mt-6 md:mt-10">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-3 md:gap-y-4 gap-x-8 md:gap-x-16">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 font-medium shrink-0">Tipe Pembayaran</span>
                        <span class="font-bold text-gray-900 capitalize text-right">{{ $latestPayment->payment_type ?? 'Menunggu' }}</span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 font-medium shrink-0">Metode Bayar</span>
                        <span class="font-bold text-gray-900 uppercase text-right">{{ $latestPayment->method ?? '-' }}</span>
                    </div>

                    <div class="md:hidden border-t border-gray-200/60 my-1"></div>

                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 font-medium shrink-0">Total Harga</span>
                        <span class="font-bold text-gray-900 text-right">Rp {{ number_format($totalHarga, 0, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 font-medium shrink-0">Sudah Dibayar</span>
                        <span class="font-bold text-green-600 text-right">Rp {{ number_format($sudahDibayar, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="flex justify-between items-center mt-5 md:mt-6 pt-5 md:pt-6 border-t border-gray-200">
                    <span class="text-base md:text-lg font-bold text-gray-800">Sisa Tagihan</span>

                    @if($sisaTagihan == 0)
                        <span class="px-4 py-1.5 md:px-5 md:py-2 bg-green-100 text-green-700 rounded-lg font-black tracking-widest uppercase text-xs md:text-sm border border-green-200 shadow-sm">
                            LUNAS
                        </span>
                    @else
                        <span class="text-xl md:text-2xl font-black text-red-500 tracking-tight">
                            Rp {{ number_format($sisaTagihan, 0, ',', '.') }}
                        </span>
                    @endif
                </div>
            </div>

            @if($overallStatus === 'pending' && isset($latestPayment->payment_url))
                <div class="flex justify-end mt-6 md:mt-8">
                    <a href="{{ $latestPayment->payment_url }}" target="_blank" class="flex justify-center items-center gap-2 w-full md:w-auto bg-primary hover:bg-blue-800 text-white font-semibold py-3.5 px-6 md:px-10 rounded-xl shadow-lg shadow-blue-500/30 transition-all text-sm md:text-base">
                        Lanjutkan Pembayaran
                        <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>
            @endif

        </div>
    </div>
</div>
@endsection
