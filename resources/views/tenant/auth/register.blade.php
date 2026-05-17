@extends('tenant.layouts.guest')
@section('title', 'Daftar Akun')

@section('content')
<div class="w-full max-w-5xl bg-white rounded-4xl md:rounded-[3rem] shadow-xl shadow-gray-200/50 border border-gray-100 flex flex-col md:flex-row overflow-hidden min-h-137.5">

    <div class="md:w-5/12 bg-linear-to-br from-blue-500 to-primary p-10 md:p-16 flex flex-col justify-center items-center text-center text-white relative overflow-hidden md:flex">
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

    <div class="w-full md:w-7/12 p-8 md:p-12 lg:p-16 flex flex-col justify-center">

        <div class="md:hidden flex flex-col items-center mb-8">
            <div class="w-20 h-20 bg-linear-to-br from-blue-500 to-primary rounded-full shadow-lg flex items-center justify-center text-3xl mb-4">⚽</div>
            <h1 class="text-2xl font-bold text-gray-900">Daftar Akun</h1>
        </div>

        <h1 class="hidden md:block text-3xl md:text-4xl font-bold text-gray-900 mb-8">Daftar</h1>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 px-5 py-4 rounded-xl mb-6 text-sm">
                <span class="font-bold">Registrasi Gagal!</span>
                <ul class="mt-2 ml-5 list-disc marker:text-red-400 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST" class="flex flex-col gap-4 md:gap-5">
            @csrf

            <div>
                <label for="name" class="block text-gray-700 text-xs md:text-sm font-medium mb-2">Nama Lengkap</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Masukkan nama lengkap Anda" required
                    class="w-full px-5 py-3 md:py-3.5 bg-gray-50 border {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-gray-200' }} rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none">
                @error('name') <span class="text-red-500 text-xs font-medium mt-1.5 block">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
                <div>
                    <label for="email" class="block text-gray-700 text-xs md:text-sm font-medium mb-2">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Masukkan email" required
                        class="w-full px-5 py-3 md:py-3.5 bg-gray-50 border {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-200' }} rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none">
                    @error('email') <span class="text-red-500 text-xs font-medium mt-1.5 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="phone" class="block text-gray-700 text-xs md:text-sm font-medium mb-2">Nomor HP</label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="08123456789" required
                        class="w-full px-5 py-3 md:py-3.5 bg-gray-50 border {{ $errors->has('phone') ? 'border-red-400 bg-red-50' : 'border-gray-200' }} rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none">
                    @error('phone') <span class="text-red-500 text-xs font-medium mt-1.5 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
                <div>
                    <label for="password" class="block text-gray-700 text-xs md:text-sm font-medium mb-2">Password</label>
                    <input type="password" id="password" name="password" placeholder="Min. 8 karakter" required
                        class="w-full px-5 py-3 md:py-3.5 bg-gray-50 border {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-200' }} rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none">
                    @error('password') <span class="text-red-500 text-xs font-medium mt-1.5 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-gray-700 text-xs md:text-sm font-medium mb-2">Konfirmasi Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Ketik ulang password" required
                        class="w-full px-5 py-3 md:py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none">
                </div>
            </div>

            <button type="submit" class="w-full bg-primary hover:bg-blue-800 text-white font-semibold py-3.5 md:py-4 rounded-xl shadow-lg shadow-primary/30 transition-all mt-4 text-sm md:text-base tracking-wide">
                Daftar Sekarang
            </button>
        </form>

        <div class="flex items-center gap-4 my-6 md:my-8">
            <div class="flex-1 h-px bg-gray-200"></div>
            <span class="text-[11px] md:text-xs font-medium text-gray-400 uppercase tracking-wider">Atau</span>
            <div class="flex-1 h-px bg-gray-200"></div>
        </div>

        <button type="button" class="w-full flex justify-center items-center gap-3 bg-white hover:bg-gray-50 text-gray-700 font-medium py-3 md:py-3.5 px-6 border border-gray-200 rounded-xl transition-all text-sm shadow-sm mb-6">
            <svg class="w-5 h-5" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/><path d="M1 1h22v22H1z" fill="none"/></svg>
            Daftar dengan Google
        </button>

        <div class="text-center text-sm md:text-base text-gray-600">
            Sudah punya akun? <a href="{{ route('login') }}" class="text-primary font-bold hover:text-blue-800 transition-colors ml-1">Masuk di sini</a>
        </div>
    </div>
</div>
@endsection
