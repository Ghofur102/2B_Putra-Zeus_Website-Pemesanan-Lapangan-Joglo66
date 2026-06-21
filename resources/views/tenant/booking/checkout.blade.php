@extends('tenant.layouts.app')

@section('title', 'Pembayaran')

@section('content')
<div id="checkout-app"
     data-reference="{{ $reference }}"
     data-redirect-url="{{ route('tenant.booking.dashboard') }}"
     class="mt-12">

    <x-tenant-card variant="default" class="max-w-md mx-auto text-center">
        <div class="w-16 h-16 bg-primary-light text-primary rounded-tenant-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>

        <h2 class="text-2xl font-bold text-gray-900 mb-2">Selesaikan Pembayaran</h2>
        <p class="text-gray-500 mb-6 text-sm leading-relaxed">
            Silakan selesaikan pembayaran sebesar <span class="font-bold text-primary">Rp {{ number_format($amountToPay, 0, ',', '.') }}</span> untuk mengonfirmasi jadwal lapangan Anda.
        </p>

        <x-tenant-button id="pay-button" variant="primary" class="w-full py-4 text-base tracking-wide shadow-tenant-md">
            Bayar Sekarang
        </x-tenant-button>
    </x-tenant-card>
</div>
@endsection

@push('scripts')
<script
    src="https://app-sandbox.duitku.com/lib/js/duitku.js"
    integrity="sha384-YqvaXfo86/6TKI/+JygjW83ldCkFdoe2IyIDBDIO5pChVZhEpx3/Yws5ui2NnAR6"
    crossorigin="anonymous">
</script>
@endpush
