<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Joglo66</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.jpg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 font-sans antialiased min-h-screen flex flex-col">

    <nav class="w-full px-6 md:px-16 py-5 flex justify-between items-center border-b border-gray-200/60 bg-white shadow-tenant-sm">
        <a href="{{ route('tenant.booking.dashboard') }}"
        class="w-12 h-12 md:w-14 md:h-14 flex items-center justify-center shadow-tenant-md"
        >
            <img src="{{ asset('logo.jpg') }}" alt="Joglo66" class="rounded-tenant-full">
        </a>
        <div class="flex items-center gap-4 text-xs md:text-sm font-semibold">
            <a href="{{ route('login') }}" class="{{ request()->routeIs('login') ? 'bg-primary text-white px-5 py-2 rounded-tenant-full shadow-tenant-md hover:bg-primary-hover transition-all' : 'text-gray-600 hover:text-primary transition-colors' }}">Masuk</a>
            <a href="{{ route('register') }}" class="{{ request()->routeIs('register') ? 'bg-primary text-white px-5 py-2 rounded-tenant-full shadow-tenant-md hover:bg-primary-hover transition-all' : 'text-gray-600 hover:text-primary transition-colors' }}">Daftar</a>
        </div>
    </nav>

    <main class="flex-1 flex justify-center items-center p-4 md:p-10">
        @yield('content')
    </main>

</body>
</html>
