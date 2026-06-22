@extends('tenant.layouts.app')

@section('title', 'Pemesanan Berhasil')

@section('content')
<div class="max-w-2xl mx-auto py-10 px-4">

    <div class="text-center mb-10">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-green-50 rounded-tenant-full border border-green-200 shadow-tenant-sm text-green-500 mb-4 animate-bounce">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h2 class="text-2xl font-black text-gray-900 tracking-tight">Pembayaran & Pemesanan Berhasil!</h2>
        <p class="text-sm text-gray-500 mt-2">Jadwal lapangan Anda telah dikunci dengan aman di dalam sistem kami.</p>
    </div>

    <x-tenant-card variant="default" class="overflow-hidden border border-gray-100 shadow-tenant-md mb-8 p-0">
        <div class="bg-gray-50/70 border-b border-gray-100 px-6 py-4 flex justify-between items-center">
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Nomor Booking</span>
                <span class="text-sm font-mono font-bold text-gray-800">#JGL-{{ str_pad($booking->id, 6, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="text-right">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Status Transaksi</span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-tenant-full text-xs font-bold bg-green-50 text-green-700 border border-green-200">
                    <span class="w-1.5 h-1.5 rounded-tenant-full bg-green-500"></span> LUNAS
                </span>
            </div>
        </div>

        <div class="p-6 space-y-6">
            <div class="flex justify-between items-start border-b border-gray-50 pb-4">
                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Fasilitas Lapangan</h4>
                    <p class="text-base font-bold text-gray-900">{{ $booking->field->name ?? 'Lapangan Joglo66' }}</p>
                </div>
                <div class="text-right">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Tanggal Bermain</h4>
                    <p class="text-sm font-bold text-gray-800">
                        @if($booking->details && $booking->details->first())
                            {{ \Carbon\Carbon::parse($booking->details->first()->play_date)->translatedFormat('d F Y') }}
                        @else
                            -
                        @endif
                    </p>
                </div>
            </div>

            <div>
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Daftar Slot Jam Yang Dipesan</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($booking->details as $detail)
                        <div class="flex items-center justify-between p-3 bg-gray-50/60 border border-gray-100 rounded-tenant-md">
                            <span class="text-sm font-bold text-gray-700">
                                {{ substr($detail->start_play_time, 0, 5) }} - {{ substr($detail->end_play_time, 0, 5) }} WIB
                            </span>
                            <span class="text-xs font-semibold text-gray-500">
                                Rp {{ number_format($detail->price, 0, ',', '.') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100 flex justify-between items-center bg-primary-light/40 -mx-6 -mb-6 p-6">
                <span class="text-sm font-bold text-gray-700">Total Pembayaran</span>
                <span class="text-xl font-black text-primary">
                    Rp {{ number_format($booking->details->sum('price'), 0, ',', '.') }}
                </span>
            </div>
        </div>
    </x-tenant-card>

    <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
        <x-tenant-button :href="route('tenant.booking.dashboard')" variant="danger" class="w-full sm:w-auto px-8 py-3 bg-gray-100 text-gray-700 border border-gray-200 hover:bg-gray-200">
            Sewa Lapangan Lagi
        </x-tenant-button>

        <x-tenant-button :href="route('tenant.booking.history.show', $booking->id)" variant="primary" class="w-full sm:w-auto px-8 py-3 shadow-tenant-md">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Lihat Riwayat Pesanan
        </x-tenant-button>
    </div>

</div>
@endsection
