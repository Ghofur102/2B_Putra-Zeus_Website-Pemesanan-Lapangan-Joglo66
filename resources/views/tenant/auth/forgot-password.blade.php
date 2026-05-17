@extends('tenant.layouts.guest')
@section('title', 'Lupa Password')

@section('content')
<div class="w-full max-w-md bg-white rounded-4xl shadow-xl shadow-gray-200/50 border border-gray-100 p-8 md:p-12 flex flex-col relative overflow-hidden">

    <div class="flex flex-col items-center text-center mb-8">
        <div class="w-20 h-20 bg-linear-to-br from-blue-500 to-primary rounded-full shadow-lg shadow-primary/20 flex items-center justify-center text-3xl mb-6">
            ⚽
        </div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-3">Lupa Password?</h1>
        <p class="text-sm text-gray-500 leading-relaxed max-w-70">
            Masukkan email Anda di bawah ini dan kami akan mengirimkan tautan untuk mengatur ulang password Anda.
        </p>
    </div>

    @if (session('status'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-4 rounded-xl mb-6 text-sm font-medium text-center">
            ✓ {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-600 px-5 py-4 rounded-xl mb-6 text-sm font-medium text-center">
            ⚠ {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('password.email') }}" method="POST" class="flex flex-col gap-5">
        @csrf

        <div>
            <label for="email" class="block text-gray-700 text-sm font-medium mb-2">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Masukkan email Anda" required
                class="w-full px-5 py-3.5 bg-gray-50 border {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-200' }} rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none text-center">

            @error('email')
                <span class="text-red-500 text-xs font-medium mt-1.5 block text-center">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="w-full bg-primary hover:bg-blue-800 text-white font-semibold py-4 rounded-xl shadow-lg shadow-primary/30 transition-all mt-2 text-sm md:text-base tracking-wide">
            Kirim Link Reset Password
        </button>
    </form>

    <div class="mt-8 text-center text-sm text-gray-600">
        Ingat password Anda? <a href="{{ route('login') }}" class="text-primary font-bold hover:text-blue-800 transition-colors ml-1">Masuk di sini</a>
    </div>

</div>
@endsection
