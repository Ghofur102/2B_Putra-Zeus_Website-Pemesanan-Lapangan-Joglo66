@extends('tenant.layouts.app')

@section('title', 'Konfirmasi Penyewaan')

@section('content')
<div id="checkout-app">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Selesaikan Pemesanan</h2>
        <p class="text-gray-500">Lengkapi data di bawah ini untuk melanjutkan pembayaran.</p>
    </div>

    <form action="{{ route('tenant.booking.store') }}" method="POST">
        @csrf
        <input type="hidden" name="field_id" value="{{ $field->id }}">
        <input type="hidden" name="booking_data" value="{{ json_encode($groupedSlots) }}">
        <input type="hidden" name="total_price" value="{{ $totalPrice }}">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

            @component('tenant.components.card-box')
                @slot('title', 'Info Penyewa')
                @slot('icon')
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                @endslot

                <div class="flex flex-col gap-4">
                    <div>
                        <label for="team_name" class="block text-sm font-semibold text-gray-700 mb-1">Nama Tim / Penyewa <span class="text-red-500">*</span></label>
                        <input type="text" id="team_name" name="team_name" required placeholder="Masukkan nama tim" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 bg-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>

                    <div>
                        <label for="tenant_whatsapp" class="block text-sm font-semibold text-gray-700 mb-1">Nomor WhatsApp</label>
                        <input type="text" id="tenant_whatsapp" value="{{ auth()->user()->phone_number ?? auth()->user()->phone ?? '-' }}" readonly class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-100 text-gray-500 cursor-not-allowed focus:outline-none">
                        <p class="text-[11px] text-gray-400 mt-1 font-medium italic">*Nomor diambil dari data profil Anda.</p>
                    </div>

                    <div>
                        <label for="tenant_email" class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                        <input type="email" id="tenant_email" value="{{ auth()->user()->email }}" readonly class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-100 text-gray-500 cursor-not-allowed focus:outline-none">
                        <p class="text-[11px] text-gray-400 mt-1 font-medium italic">*Email diambil dari data profil Anda.</p>
                    </div>
                </div>
            @endcomponent

            @component('tenant.components.card-box')
                @slot('title', 'Detail Penyewaan')
                @slot('icon')
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                @endslot

                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-col gap-3">
                    <div class="flex justify-between border-b border-dashed border-gray-200 pb-2">
                        <span class="text-gray-500 text-sm">Lapangan</span>
                        <span class="font-bold text-gray-800 text-sm">{{ $field->name }}</span>
                    </div>

                    @foreach($groupedSlots as $date => $slots)
                    <div class="flex flex-col border-b border-dashed border-gray-200 pb-2">
                        <span class="text-gray-600 font-semibold text-sm mb-1">{{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}</span>
                        <div class="flex flex-col">
                            @foreach($slots as $slot)
                                <div class="flex justify-between items-center bg-gray-50 p-2 rounded-md mb-1">
                                    <span class="font-bold text-gray-800 text-sm">{{ $slot['jam'] }} - {{ $slot['jam_akhir'] }}</span>
                                    <span class="text-primary text-sm font-semibold">Rp {{ number_format($slot['harga'], 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach

                    <div class="flex justify-between pt-1">
                        <span class="text-gray-600 font-bold">Total Harga</span>
                        <span class="font-bold text-primary text-lg">Rp {{ number_format($totalPrice, 0, ',', '.') }}</span>
                    </div>
                </div>
            @endcomponent
        </div>

        <div class="bg-gray-100 rounded-2xl p-6 md:p-8 border border-gray-200 shadow-sm mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                <div>
                    <h3 class="text-base font-bold text-gray-800 mb-4">Opsi Pembayaran</h3>
                    <div class="flex flex-col gap-3">
                        <label for="pay_dp" class="flex items-center p-4 bg-white border border-gray-300 rounded-xl cursor-pointer hover:border-primary transition group relative">
                            <input type="radio" id="pay_dp" name="payment_type" value="down payment" class="w-5 h-5 text-primary border-gray-300 focus:ring-primary" required>
                            <span class="ml-3 font-semibold text-gray-700">DP (Uang Muka 50%)</span>
                            <span class="absolute right-4 font-bold text-primary">Rp {{ number_format($totalPrice / 2, 0, ',', '.') }}</span>
                        </label>

                        <label for="pay_full" class="flex items-center p-4 bg-white border border-gray-300 rounded-xl cursor-pointer hover:border-primary transition group relative">
                            <input type="radio" id="pay_full" name="payment_type" value="final payment" class="w-5 h-5 text-primary border-gray-300 focus:ring-primary" required>
                            <span class="ml-3 font-semibold text-gray-700">Bayar Lunas</span>
                            <span class="absolute right-4 font-bold text-primary">Rp {{ number_format($totalPrice, 0, ',', '.') }}</span>
                        </label>
                    </div>
                </div>

                <div class="flex flex-col h-full">
                    <label for="booking_notes" class="block text-base font-bold text-gray-800 mb-4 cursor-pointer">
                        Catatan Khusus <span class="text-gray-400 font-normal text-sm">(Opsional)</span>
                    </label>
                    <textarea id="booking_notes" name="notes" placeholder="Tuliskan catatan tambahan jika ada..." class="w-full flex-1 min-h-25 p-4 rounded-xl border border-gray-300 bg-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary resize-none"></textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-4">
            <a href="javascript:history.back()" class="px-8 py-3 bg-white border border-gray-300 text-gray-700 font-bold rounded-full hover:bg-gray-50 transition shadow-sm">Kembali</a>
            <button type="submit" class="px-8 py-3 bg-primary text-white font-bold rounded-full hover:bg-blue-800 transition shadow-md">Lanjutkan Pembayaran</button>
        </div>
    </form>
</div>
@endsection
