@extends('tenant.layouts.app')

@section('title', 'Pembayaran')

@section('content')
<div class="max-w-xl mx-auto bg-white p-8 rounded-2xl shadow-sm border border-gray-200 text-center mt-10">
    <div class="w-20 h-20 bg-blue-50 text-primary rounded-full flex items-center justify-center mx-auto mb-6">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
        </svg>
    </div>

    <h2 class="text-2xl font-bold text-gray-800 mb-2">Selesaikan Pembayaran</h2>
    <p class="text-gray-500 mb-6">
        Silakan selesaikan pembayaran sebesar <span class="font-bold text-primary">Rp {{ number_format($amountToPay, 0, ',', '.') }}</span> untuk mengonfirmasi jadwal lapangan Anda.
    </p>

    <button id="pay-button" class="w-full bg-primary text-white font-bold py-4 rounded-xl hover:bg-blue-800 transition shadow-md text-lg">
        Bayar Sekarang
    </button>
</div>
@endsection

@push('scripts')
<script
    src="https://app-sandbox.duitku.com/lib/js/duitku.js"
    integrity="sha384-YqvaXfo86/6TKI/+JygjW83ldCkFdoe2IyIDBDIO5pChVZhEpx3/Yws5ui2NnAR6"
    crossorigin="anonymous">
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const payButton = document.getElementById('pay-button');

        payButton.addEventListener('click', function() {
            if (typeof checkout === 'undefined') {
                alert('Sistem pembayaran belum siap, tunggu beberapa detik atau muat ulang halaman.');
                return;
            }

            checkout.process('{{ $reference }}', {
                successEvent: function(result) {
                    window.location.href = "{{ route('tenant.booking.dashboard') }}";
                },
                pendingEvent: function(result) {
                    window.location.href = "{{ route('tenant.booking.dashboard') }}";
                },
                errorEvent: function(result) {
                    alert('Terjadi kesalahan pada pembayaran');
                },
                closeEvent: function(result) {
                    console.log('User menutup popup');
                }
            });
        });

        setTimeout(function() {
            payButton.click();
        }, 1000);
    });
</script>
@endpush
