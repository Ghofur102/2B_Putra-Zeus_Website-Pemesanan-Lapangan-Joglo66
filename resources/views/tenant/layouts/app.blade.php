<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Beranda') - Joglo66</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.jpg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white text-gray-800 font-sans antialiased min-h-screen">

    @include('tenant.layouts.navbar')

    @include('tenant.components.alert')

    <main class="max-w-4xl mx-auto px-4 py-10">
        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>
