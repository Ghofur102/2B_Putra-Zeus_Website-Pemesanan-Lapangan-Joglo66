@extends('tenant.layouts.app')

@section('title', 'Profil Saya')

@section('content')
<div class="max-w-5xl mx-auto mt-16 mb-10 relative">

    <div class="absolute -top-5 left-1/2 -translate-x-1/2 bg-primary text-white px-8 py-2.5 rounded-full font-semibold text-sm shadow-md z-10 tracking-wide">
        Profil Saya
    </div>

    <div class="bg-white rounded-3xl p-8 md:p-12 shadow-sm border border-gray-200 flex flex-col md:flex-row gap-10 md:gap-14 relative">

        <div class="flex-1 flex flex-col">
            <h2 class="text-gray-800 text-xl font-bold mb-6 border-b border-gray-100 pb-3">Edit Profil</h2>

            <form action="{{ route('profile.update') }}" method="POST" class="flex flex-col gap-5">
                @csrf
                <div>
                    <label for="name" class="block text-gray-600 text-sm font-medium mb-2">Nama Lengkap</label>
                    <input type="text" id="name" name="name" value="{{ $user->name }}" required
                        class="w-full px-4 mb-3 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-all shadow-sm">
                    @error('name') <span class="text-red-500 text-xs font-medium mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="email" class="block text-gray-600 text-sm font-medium mb-2">Email</label>
                    <input type="email" id="email" name="email" value="{{ $user->email }}" required
                        class="w-full px-4 mb-3 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-all shadow-sm">
                    @error('email') <span class="text-red-500 text-xs font-medium mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="phone" class="block text-gray-600 text-sm font-medium mb-2">Nomor HP</label>
                    <input type="tel" id="phone" name="phone" value="{{ $user->phone }}" required
                        class="w-full px-4 mb-3 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-all shadow-sm">
                    @error('phone') <span class="text-red-500 text-xs font-medium mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col sm:flex-row gap-3 mt-4">
                    <button type="submit" class="bg-primary mb-3 hover:bg-blue-800 text-white font-medium py-3 px-8 rounded-xl shadow-sm transition-all text-sm">Simpan</button>
                </div>
            </form>
        </div>

        <div class="hidden md:block w-px bg-gray-200"></div>

        <div class="flex-1 flex flex-col">
            <h2 class="text-gray-800 text-xl font-bold mb-6 border-b border-gray-100 pb-3">Ubah Password</h2>

            <form action="{{ route('password.change') }}" method="POST" class="flex flex-col gap-5">
                @csrf
                <div>
                    <label for="current_password" class="block text-gray-600 text-sm font-medium mb-2">Password Saat Ini</label>
                    <input type="password" id="current_password" name="current_password" required
                        class="w-full px-4 mb-3 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 focus:outline-none focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-all shadow-sm">
                    @error('current_password') <span class="text-red-500 text-xs font-medium mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="password" class="block text-gray-600 text-sm font-medium mb-2">Password Baru</label>
                    <input type="password" id="password" name="password" placeholder="Minimal 8 karakter" required
                        class="w-full px-4 mb-3 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-all shadow-sm">
                    @error('password') <span class="text-red-500 text-xs font-medium mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-gray-600 text-sm font-medium mb-2">Konfirmasi Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                        class="w-full mb-3 px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 focus:outline-none focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-all shadow-sm">
                </div>

                <div class="flex flex-col sm:flex-row gap-3 mt-4">
                    <button type="submit" class="bg-primary mb-3 hover:bg-blue-800 text-white font-medium py-3 px-8 rounded-xl shadow-sm transition-all text-sm">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="text-center mt-5">
        <form action="{{ route('logout') }}" method="POST" class="inline-block">
            @csrf
            <button type="submit" class="bg-red-600 text-white hover:bg-red-100 border border-red-200 font-medium py-2.5 px-8 rounded-xl shadow-sm transition-colors text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Logout
            </button>
        </form>
    </div>
@endsection
