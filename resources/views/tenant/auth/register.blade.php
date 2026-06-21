@extends('tenant.layouts.guest')
@section('title', 'Daftar Akun')

@section('content')
    <div
        class="w-full max-w-5xl bg-white rounded-tenant-lg shadow-tenant-md border border-gray-100 flex flex-col md:flex-row overflow-hidden min-h-[560px]">

        <div
            class="md:w-5/12 bg-gradient-to-br from-primary-dark to-primary p-8 md:p-16 flex flex-col justify-center items-center text-center text-white relative overflow-hidden md:flex">
            <div class="absolute inset-0 bg-black/5"></div>
            <div class="relative z-10 flex flex-col items-center">
                <h2 class="text-lg md:text-xl font-medium mb-6 tracking-wide opacity-95">Selamat Datang Di</h2>
                <div
                    class="w-32 h-32 md:w-40 md:h-40 bg-white/10 rounded-tenant-full border border-white/20 shadow-tenant-md flex items-center justify-center text-5xl md:text-6xl mb-8 backdrop-blur-sm">
                    ⚽
                </div>
                <p class="text-sm md:text-base leading-relaxed opacity-90 max-w-64">
                    Platform booking lapangan olahraga terpercaya dengan layanan terbaik untuk Anda
                </p>
            </div>
        </div>

        <div class="w-full md:w-7/12 p-8 md:p-12 lg:p-16 flex flex-col justify-center">

            <div class="md:hidden flex flex-col items-center mb-8">
                <div
                    class="w-20 h-20 bg-gradient-to-br from-primary-dark to-primary rounded-tenant-full shadow-tenant-sm flex items-center justify-center text-3xl mb-4">
                    ⚽</div>
                <h1 class="text-2xl font-bold text-gray-900">Daftar Akun</h1>
            </div>

            <h1 class="hidden md:block text-3xl md:text-4xl font-bold text-gray-900 mb-8">Daftar</h1>

            @if (session('error'))
                <div
                    class="bg-red-50 border border-red-200 text-red-600 px-4 py-4 rounded-tenant-lg mb-6 text-sm font-medium">
                    ⚠ {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-4 rounded-tenant-lg mb-6 text-sm">
                    <span class="font-bold">Registrasi Gagal!</span>
                    <ul class="mt-2 ml-4 list-disc marker:text-red-400 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('register') }}" method="POST" class="flex flex-col gap-4">
                @csrf

                <x-tenant-input name="name" label="Nama Lengkap" placeholder="Masukkan nama lengkap Anda" required />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-tenant-input name="email" label="Email" type="email" placeholder="Masukkan email Anda"
                        required />

                    <x-tenant-input name="phone" label="Nomor HP" type="tel" placeholder="08123456789" required />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-tenant-input name="password" label="Password" type="password" placeholder="Min. 8 karakter"
                        required />

                    <x-tenant-input name="password_confirmation" label="Konfirmasi Password" type="password"
                        placeholder="Ketik ulang password" required />
                </div>

                <x-tenant-button type="submit" variant="primary" class="w-full py-4 tracking-wide">
                    Daftar Sekarang
                </x-tenant-button>
            </form>

            <div class="flex items-center gap-4 my-6">
                <div class="flex-1 h-px bg-gray-200"></div>
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Atau</span>
                <div class="flex-1 h-px bg-gray-200"></div>
            </div>

            <div class="text-center text-sm md:text-base text-gray-600">
                Sudah punya akun? <a href="{{ route('login') }}"
                    class="text-primary font-bold hover:text-primary-hover transition-colors ml-1">Masuk di sini</a>
            </div>
        </div>
    </div>
@endsection
