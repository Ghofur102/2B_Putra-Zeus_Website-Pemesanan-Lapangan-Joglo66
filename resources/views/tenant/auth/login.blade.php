@extends('tenant.layouts.guest')
@section('title', 'Login')

@section('content')
<div class="w-full max-w-5xl bg-white rounded-tenant-lg shadow-tenant-md border border-gray-100 flex flex-col md:flex-row overflow-hidden min-h-[560px]">

    <div class="md:w-5/12 bg-gradient-to-br from-primary-dark to-primary p-8 md:p-16 flex flex-col justify-center items-center text-center text-white relative overflow-hidden">
        <div class="absolute inset-0 bg-black/5"></div>
        <div class="relative z-10 flex flex-col items-center">
            <h2 class="text-lg md:text-xl font-medium mb-6 tracking-wide opacity-95">Selamat Datang Di</h2>
            <div class="w-32 h-32 md:w-40 md:h-40 bg-white/10 rounded-tenant-full border border-white/20 shadow-tenant-md flex items-center justify-center text-5xl md:text-6xl mb-8 backdrop-blur-sm">
                ⚽
            </div>
            <p class="text-sm md:text-base leading-relaxed opacity-90 max-w-64">
                Platform booking lapangan olahraga terpercaya dengan layanan terbaik untuk Anda
            </p>
        </div>
    </div>

    <div class="md:w-7/12 p-8 md:p-16 flex flex-col justify-center">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-8 text-center md:text-left">Login</h1>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-4 rounded-tenant-lg mb-6 text-sm">
                <span class="font-bold">Login Gagal!</span>
                <ul class="mt-2 ml-4 list-disc marker:text-red-400 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-4 rounded-tenant-lg mb-6 text-sm font-medium">
                ❌ {{ session('error') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-4 rounded-tenant-lg mb-6 text-sm font-medium">
                ⚠ {{ session('warning') }}
            </div>
        @endif

        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-4 rounded-tenant-lg mb-6 text-sm font-medium">
                ✓ {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="flex flex-col gap-4">
            @csrf

            <x-tenant-input
                name="email"
                label="Email"
                type="email"
                placeholder="Masukkan email Anda"
                required
            />

            <div>
                <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                <div class="relative">
                    <input type="password" id="password" name="password" placeholder="Masukkan password Anda" required
                        class="w-full pl-4 pr-12 py-3 bg-gray-50 border border-gray-200 rounded-tenant-md text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none text-sm">

                    <button type="button" class="toggle-password absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-primary transition-colors cursor-pointer" data-target="password">
                        <svg class="w-5 h-5 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2 mt-2">
                <label class="flex items-center gap-2 cursor-pointer group">
                    <input type="checkbox" name="remember_me" value="1" class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2 cursor-pointer transition-all">
                    <span class="text-sm text-gray-600 font-medium group-hover:text-gray-900 transition-colors">Ingat saya</span>
                </label>
            </div>

            <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white font-semibold py-4 rounded-tenant-md shadow-tenant-sm transition-all mt-4 text-sm md:text-base tracking-wide cursor-pointer">
                Masuk
            </button>
        </form>

        <div class="flex items-center gap-4 my-8">
            <div class="flex-1 h-px bg-gray-200"></div>
            <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Belum punya akun?</span>
            <div class="flex-1 h-px bg-gray-200"></div>
        </div>

        <div class="text-center">
            <a href="{{ route('register') }}" class="text-primary font-bold hover:text-primary-hover transition-colors">Daftar di sini</a>
        </div>
    </div>
</div>
@endsection
