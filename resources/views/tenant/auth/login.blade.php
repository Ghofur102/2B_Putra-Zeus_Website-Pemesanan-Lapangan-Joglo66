@extends('tenant.layouts.guest')
@section('title', 'Login')

@section('content')
<div class="w-full max-w-5xl bg-white rounded-4xl md:rounded-[3rem] shadow-xl shadow-gray-200/50 border border-gray-100 flex flex-col md:flex-row overflow-hidden min-h-137.5">

    <div class="md:w-5/12 bg-linear-to-br from-blue-500 to-primary p-10 md:p-16 flex flex-col justify-center items-center text-center text-white relative overflow-hidden">
        <div class="absolute inset-0 bg-black/5"></div>
        <div class="relative z-10 flex flex-col items-center">
            <h2 class="text-lg md:text-xl font-medium mb-6 tracking-wide opacity-95">Selamat Datang Di</h2>
            <div class="w-32 h-32 md:w-40 md:h-40 bg-white/10 rounded-full border border-white/20 shadow-xl flex items-center justify-center text-5xl md:text-6xl mb-8 backdrop-blur-sm">
                ⚽
            </div>
            <p class="text-sm md:text-base leading-relaxed opacity-90 max-w-65">
                Platform booking lapangan olahraga terpercaya dengan layanan terbaik untuk Anda
            </p>
        </div>
    </div>

    <div class="md:w-7/12 p-8 md:p-16 flex flex-col justify-center">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-8 text-center md:text-left">Login</h1>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 px-5 py-4 rounded-xl mb-6 text-sm">
                <span class="font-bold">Login Gagal!</span>
                <ul class="mt-2 ml-5 list-disc marker:text-red-400 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('warning'))
            <div class="bg-amber-50 border border-amber-200 text-amber-700 px-5 py-4 rounded-xl mb-6 text-sm font-medium">
                ⚠ {{ session('warning') }}
            </div>
        @endif

        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-4 rounded-xl mb-6 text-sm font-medium">
                ✓ {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="flex flex-col gap-5">
            @csrf

            <div>
                <label for="email" class="block text-gray-700 text-sm font-medium mb-2">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Masukkan email Anda" required
                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none">
            </div>

            <div>
                <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password Anda" required
                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none">
            </div>

            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mt-2">
                <label class="flex items-center gap-2.5 cursor-pointer group">
                    <input type="checkbox" name="remember_me" value="1" class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2 cursor-pointer transition-all">
                    <span class="text-sm text-gray-600 font-medium group-hover:text-gray-900 transition-colors">Ingat saya</span>
                </label>
                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-primary hover:text-blue-800 transition-colors">Lupa password?</a>
            </div>

            <button type="submit" class="w-full bg-primary hover:bg-blue-800 text-white font-semibold py-4 rounded-xl shadow-lg shadow-primary/30 transition-all mt-4 text-sm md:text-base tracking-wide">
                Masuk
            </button>
        </form>

        <div class="flex items-center gap-4 my-8">
            <div class="flex-1 h-px bg-gray-200"></div>
            <span class="text-xs md:text-sm font-medium text-gray-400 uppercase tracking-wider">Belum punya akun?</span>
            <div class="flex-1 h-px bg-gray-200"></div>
        </div>

        <div class="text-center">
            <a href="{{ route('register') }}" class="text-primary font-bold hover:text-blue-800 transition-colors">Daftar di sini</a>
        </div>
    </div>
</div>
@endsection
