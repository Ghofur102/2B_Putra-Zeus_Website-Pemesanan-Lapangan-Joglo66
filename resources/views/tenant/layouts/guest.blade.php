<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Joglo66</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 font-sans antialiased min-h-screen flex flex-col">

    <nav class="w-full px-6 md:px-16 py-5 flex justify-between items-center border-b border-gray-200/60 bg-transparent">
        <div class="w-12 h-12 md:w-14 md:h-14 bg-linear-to-br from-primary to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-md shadow-primary/20">
            J
        </div>
        <div class="flex items-center gap-5 md:gap-10 text-xs md:text-sm font-semibold">
            <a href="{{ route('login') }}" class="{{ request()->routeIs('login') ? 'bg-primary text-white px-5 md:px-7 py-2 md:py-2.5 rounded-full shadow-md shadow-primary/30' : 'text-gray-600 hover:text-primary transition-colors' }}">Masuk</a>
            <a href="{{ route('register') }}" class="{{ request()->routeIs('register') ? 'bg-primary text-white px-5 md:px-7 py-2 md:py-2.5 rounded-full shadow-md shadow-primary/30' : 'text-gray-600 hover:text-primary transition-colors' }}">Daftar</a>
        </div>
    </nav>

    <main class="flex-1 flex justify-center items-center p-4 md:p-10">
        @yield('content')
    </main>

</body>
</html>
