@extends('tenant.layouts.app')

@section('title', 'Pemesanan Berhasil')

@section('content')
<div class="mt-12">
    <x-tenant-card variant="default" class="max-w-md mx-auto text-center">
        <div class="w-16 h-16 bg-primary-light text-primary rounded-tenant-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-5 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
        </div>

        <h2 class="text-2xl font-bold text-gray-900 mb-2">Pemesanan Berhasil Dibuat</h2>
        <p class="text-gray-500 mb-6 text-sm leading-relaxed">
            Jadwal sewa Anda telah terdaftar di sistem. Silakan buka halaman detail untuk memeriksa invoice dan rincian jadwal bermain Anda.
        </p>

        <x-tenant-button
            href="{{ route('tenant.booking.history.show', $booking->id) }}"
            variant="primary"
            class="w-full py-4 text-base tracking-wide shadow-tenant-md">
            Lihat Detail Pemesanan
        </x-tenant-button>
    </x-tenant-card>
</div>
@endsection
