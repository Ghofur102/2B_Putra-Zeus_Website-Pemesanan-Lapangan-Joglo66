<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Joglo66') - Joglo66</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-[#374151] antialiased">
    <nav class="bg-[#1E3A5F]">
        <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center font-bold text-[#1E3A5F] text-sm">J</div>
                <span class="text-white font-bold text-lg">Joglo66</span>
            </div>
            <div class="flex items-center gap-6 text-white text-sm font-medium">
                <a href="/" class="hover:text-blue-200 transition">Home</a>
                <a href="{{ route('booking.history') }}" class="hover:text-blue-200 transition">Booking</a>
                <a href="#" class="hover:text-blue-200 transition">Profil</a>
            </div>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-4 py-6">
        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>
