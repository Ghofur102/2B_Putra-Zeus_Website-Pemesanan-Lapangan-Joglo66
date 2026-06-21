<nav class="bg-primary shadow-tenant-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">

            <div class="shrink-0 flex items-center">
                <a href="{{ route('tenant.booking.dashboard') }}" class="text-white text-xl font-bold tracking-wide">Joglo66</a>
            </div>

            <div class="hidden md:flex md:items-center md:space-x-4">
                <a href="{{ route('tenant.booking.dashboard') }}" class="text-white hover:bg-primary-dark px-3 py-2 rounded-tenant-md text-sm font-medium transition">Booking Lapangan</a>
                <a href="{{ route('tenant.booking.transaction') }}" class="text-white hover:bg-primary-dark px-3 py-2 rounded-tenant-md text-sm font-medium transition">Riwayat Transaksi</a>
                <a href="{{ route('profile.show') }}" class="text-white hover:bg-primary-dark px-3 py-2 rounded-tenant-md text-sm font-medium transition">Profil</a>

                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-red-200 hover:bg-red-900/40 px-3 py-2 rounded-tenant-md text-sm font-medium transition cursor-pointer">Keluar</button>
                </form>
            </div>

            <div class="flex items-center md:hidden">
                <button type="button" id="mobile-menu-button" class="inline-flex items-center justify-center p-2 rounded-tenant-md text-white hover:bg-primary-dark focus:outline-none transition" aria-controls="mobile-menu" aria-expanded="false">
                    <span class="sr-only">Menu Utama</span>
                    <svg class="block h-6 w-6" id="icon-menu-closed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg class="hidden h-6 w-6" id="icon-menu-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div class="hidden md:hidden bg-primary-dark border-t border-primary-hover" id="mobile-menu">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
            <a href="{{ route('tenant.booking.dashboard') }}" class="block text-white hover:bg-primary px-3 py-2 rounded-tenant-md text-base font-medium transition">Booking Lapangan</a>
            <a href="{{ route('tenant.booking.transaction') }}" class="block text-white hover:bg-primary px-3 py-2 rounded-tenant-md text-base font-medium transition">Riwayat Transaksi</a>
            <a href="{{ route('profile.show') }}" class="block text-white hover:bg-primary px-3 py-2 rounded-tenant-md text-base font-medium transition">Profil</a>

            <form action="{{ route('logout') }}" method="POST" class="block w-full">
                @csrf
                <button type="submit" class="block w-full text-left text-red-200 hover:bg-red-900/40 px-3 py-2 rounded-tenant-md text-base font-medium transition cursor-pointer">Keluar</button>
            </form>
        </div>
    </div>
</nav>
