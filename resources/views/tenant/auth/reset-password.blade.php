@extends('tenant.layouts.guest')
@section('title', 'Reset Password')

@section('content')
<div class="w-full max-w-md bg-white rounded-4xl shadow-xl shadow-gray-200/50 border border-gray-100 p-8 md:p-12 flex flex-col relative overflow-hidden">

    <div class="flex flex-col items-center text-center mb-8">
        <div class="w-20 h-20 bg-linear-to-br from-blue-500 to-primary rounded-full shadow-lg shadow-primary/20 flex items-center justify-center text-3xl mb-6">
            ⚽
        </div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-3">Reset Password</h1>
        <p class="text-sm text-gray-500 leading-relaxed max-w-70">
            Silakan masukkan password baru Anda untuk mengatur ulang akses akun.
        </p>
    </div>

    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-600 px-5 py-4 rounded-xl mb-6 text-sm font-medium text-center">
            ⚠ {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-600 px-5 py-4 rounded-xl mb-6 text-sm">
            <span class="font-bold">Reset Gagal!</span>
            <ul class="mt-2 ml-5 list-disc marker:text-red-400 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('password.update') }}" method="POST" class="flex flex-col gap-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password Baru</label>
            <input type="password" id="password" name="password" placeholder="Minimal 8 karakter" required
                class="w-full px-5 py-3.5 bg-gray-50 border {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-200' }} rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none">

            @error('password')
                <span class="text-red-500 text-xs font-medium mt-1.5 block">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-gray-700 text-sm font-medium mb-2">Konfirmasi Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Ketik ulang password baru" required
                class="w-full px-5 py-3.5 bg-gray-50 border {{ $errors->has('password_confirmation') ? 'border-red-400 bg-red-50' : 'border-gray-200' }} rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none">

            @error('password_confirmation')
                <span class="text-red-500 text-xs font-medium mt-1.5 block">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="w-full bg-primary hover:bg-blue-800 text-white font-semibold py-4 rounded-xl shadow-lg shadow-primary/30 transition-all mt-2 text-sm md:text-base tracking-wide">
            Simpan Password Baru
        </button>
    </form>

    <div class="mt-8 text-center text-sm text-gray-600">
        <a href="{{ route('login') }}" class="text-primary font-bold hover:text-blue-800 transition-colors">Kembali ke halaman Login</a>
    </div>

</div>
@endsection
