@extends('tenant.layouts.app')
@section('title', 'Verifikasi Email')

@section('content')
<div class="w-full max-w-md bg-white rounded-4xl shadow-xl shadow-gray-200/50 border border-gray-100 p-8 md:p-12 flex flex-col items-center text-center relative overflow-hidden">

    <div class="w-20 h-20 bg-linear-to-br from-blue-500 to-primary rounded-full shadow-lg shadow-primary/20 flex items-center justify-center text-white mb-6">
        <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
        </svg>
    </div>

    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-3">Cek Email Anda</h1>

    @if (session('info'))
        <div class="w-full bg-blue-50 border border-blue-200 text-blue-700 px-5 py-4 rounded-xl mb-4 text-sm font-medium">
            {{ session('info') }}
        </div>
    @endif

    @if (session('status') == 'verification-link-sent')
        <div class="w-full bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-4 rounded-xl mb-4 text-sm font-medium">
            ✓ Tautan verifikasi baru telah dikirim ke email Anda!
        </div>
    @endif

    <p class="text-sm text-gray-500 leading-relaxed mb-8">
        Kami telah mengirimkan tautan verifikasi ke email Anda. Silakan cek kotak masuk (atau folder spam) dan klik tautan tersebut untuk mengaktifkan akun Anda.
    </p>

    <form action="{{ route('verification.send') }}" method="POST" class="w-full mb-5">
        @csrf
        <button type="submit" class="w-full bg-primary hover:bg-blue-800 text-white font-semibold py-4 rounded-xl shadow-lg shadow-primary/30 transition-all text-sm md:text-base tracking-wide">
            Kirim Ulang Tautan
        </button>
    </form>

</div>
@endsection
