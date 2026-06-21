<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Beranda') - Joglo66</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.jpg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased min-h-screen flex flex-col">

    @include('tenant.layouts.navbar')

    <div class="w-full max-w-4xl mx-auto px-4 pt-4">
        @include('components.alert')
    </div>

    <main class="flex-1 max-w-4xl w-full mx-auto px-4 py-6">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
