@extends('tenant.layouts.app')

@section('title', 'Detail Transaksi')

@section('content')
<div class="w-full px-6 lg:px-15 mt-6 mb-16">

    <div class="mb-6">
        <a href="{{ url()->previous() }}" class="text-gray-500 hover:text-primary flex items-center gap-2 font-medium transition-colors text-sm w-max">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Riwayat
        </a>
    </div>

    @php
        // Ambil data pembayaran utama (Booking secara keseluruhan)
        $latestPayment = $booking->payments->sortByDesc('created_at')->first();
        $overallStatus = strtolower($latestPayment->status ?? 'unknown');

        $badgeClass = 'bg-gray-100 text-gray-600 border border-gray-200';
        if($overallStatus === 'success') $badgeClass = 'bg-green-50 text-green-600 border border-green-200';
        elseif($overallStatus === 'pending') $badgeClass = 'bg-yellow-50 text-yellow-600 border border-yellow-200';
        elseif($overallStatus === 'failed' || $overallStatus === 'expired') $badgeClass = 'bg-red-50 text-red-600 border border-red-200';
    @endphp

    <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-200">

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-gray-100 pb-6 mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Detail Pemesanan</h1>
                <p class="text-gray-500 text-sm mt-1 font-mono">No. Ref: <span class="font-bold text-gray-700">{{ $latestPayment->reference_id ?? 'Menunggu Pembayaran' }}</span></p>
            </div>
            <div class="text-left md:text-right flex flex-col items-start md:items-end gap-2">
                <span class="px-4 py-1.5 rounded-full text-sm font-bold {{ $badgeClass }} capitalize tracking-wide inline-block">
                    Status: {{ $overallStatus }}
                </span>
                <p class="text-gray-500 text-xs">Dibuat pada: {{ $booking->created_at->format('d M Y, H:i') }} WIB</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Informasi Pemesan</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Nama Tim</span><span class="font-medium text-gray-800">{{ $booking->team_name }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">No. HP</span><span class="font-medium text-gray-800">{{ $booking->customer_phone ?? '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Email</span><span class="font-medium text-gray-800">{{ $booking->customer_email ?? '-' }}</span></div>
                </div>
            </div>

            <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Informasi Lapangan</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Lapangan</span><span class="font-medium text-gray-800">{{ $booking->field->name ?? 'Lapangan Tidak Ditemukan' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Tgl Booking Induk</span><span class="font-medium text-gray-800">{{ \Carbon\Carbon::parse($booking->booking_date)->format('d F Y') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Catatan Tambahan</span><span class="font-medium text-gray-800">{{ $booking->notes ?: '-' }}</span></div>
                </div>
            </div>
        </div>

        <h3 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Rincian Sesi Disewa</h3>
        <div class="overflow-x-auto mb-8 border border-gray-200 rounded-xl">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr class="text-gray-500 text-xs uppercase tracking-wider">
                        <th class="px-5 py-3 font-medium">Tanggal Main</th>
                        <th class="px-5 py-3 font-medium">Sesi Jam</th>
                        <th class="px-5 py-3 font-medium">Status Jam</th>
                        <th class="px-5 py-3 font-medium text-right">Harga Sesi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($booking->details as $detail)
                        @php
                            // Cek apakah ada status spesifik di tabel detail, atau ambil dari relasi payment di detail
                            $detailPayment = $detail->payment->sortByDesc('created_at')->first();
                            $detailStatus = strtolower($detail->status ?? $detailPayment->status ?? 'pending');

                            $detailBadge = 'text-gray-600 bg-gray-100';
                            if($detailStatus === 'success' || $detailStatus === 'booked') $detailBadge = 'text-green-600 bg-green-50 border border-green-100';
                            elseif($detailStatus === 'pending') $detailBadge = 'text-yellow-600 bg-yellow-50 border border-yellow-100';
                            elseif($detailStatus === 'failed' || $detailStatus === 'cancelled') $detailBadge = 'text-red-600 bg-red-50 border border-red-100';
                        @endphp
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-5 py-3 text-gray-800">{{ \Carbon\Carbon::parse($detail->play_date)->format('d M Y') }}</td>
                            <td class="px-5 py-3 text-gray-800 font-medium">{{ \Carbon\Carbon::parse($detail->start_play_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($detail->end_play_time)->format('H:i') }}</td>
                            <td class="px-5 py-3">
                                <span class="px-2.5 py-1 rounded-md text-xs font-semibold capitalize {{ $detailBadge }}">
                                    {{ $detailStatus }}
                                </span>
                            </td>
                            <td class="px-5 py-3 font-medium text-gray-800 text-right">Rp {{ number_format($detail->price, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 pt-6 flex flex-col items-end">
            <div class="w-full md:w-87.5 space-y-3 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Tipe Pembayaran</span>
                    <span class="font-medium text-gray-800 capitalize bg-gray-100 px-3 py-1 rounded-md">{{ $latestPayment->payment_type ?? 'Menunggu' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Metode Bank / E-Wallet</span>
                    <span class="font-medium text-gray-800 uppercase">{{ $latestPayment->method ?? '-' }}</span>
                </div>
                <div class="flex justify-between items-center border-t border-gray-200 pt-4 mt-4">
                    <span class="text-base font-bold text-gray-800">Total Tagihan</span>
                    <span class="text-xl font-bold text-primary">Rp {{ number_format($latestPayment->amount ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>

            @if($overallStatus === 'pending' && isset($latestPayment->payment_url))
                <div class="w-full md:w-87.5 mt-6">
                    <a href="{{ $latestPayment->payment_url }}" target="_blank" class="block w-full bg-primary hover:bg-blue-800 text-white font-medium py-3.5 px-8 rounded-xl shadow-md transition-all text-center">
                        Lanjutkan Pembayaran
                    </a>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
