@extends('tenant.layouts.app')

@section('title', 'Profil Saya')

@section('content')
    <div class="max-w-2xl mx-auto mt-16 mb-8 relative">

        <div
            class="absolute -top-5 left-1/2 -translate-x-1/2 bg-primary text-white px-8 py-2.5 rounded-tenant-full font-semibold text-sm shadow-tenant-md z-10 tracking-wide">
            Profil Saya
        </div>

        <div class="bg-white rounded-tenant-lg p-8 md:p-12 shadow-tenant-sm border border-gray-200 relative">
            <h2 class="text-gray-900 text-xl font-bold mb-6 border-b border-gray-100 pb-3">Edit Profil</h2>

            <form action="{{ route('profile.update') }}" method="POST" class="flex flex-col gap-4">
                @csrf

                <x-tenant-input name="name" label="Nama Lengkap" :value="$user->name" required />

                <x-tenant-input name="email" label="Email" type="email" :value="$user->email" required />

                <x-tenant-input name="phone" label="Nomor HP" type="tel" :value="$user->phone" required />

                <div class="flex flex-col sm:flex-row gap-4 mt-4">
                    <button type="submit"
                        class="w-full sm:w-auto bg-primary hover:bg-primary-hover text-white font-medium py-3 px-8 rounded-tenant-md shadow-tenant-sm transition-all text-sm cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="text-center mt-8">
        <form action="{{ route('logout') }}" method="POST" class="inline-block">
            @csrf
            <x-tenant-button type="submit" variant="danger" class="py-2.5 px-8 gap-2 mx-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Logout Sesi
            </x-tenant-button>
        </form>
    </div>
@endsection
