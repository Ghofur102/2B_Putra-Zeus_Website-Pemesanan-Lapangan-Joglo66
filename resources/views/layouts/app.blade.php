<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Booking Lapangan')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4">
            <h1 class="text-2xl font-bold text-blue-600">Booking Lapangan Mini Soccer</h1>
        </div>
    </nav>

    @yield('content')

    <footer class="bg-white border-t mt-12">
        <div class="container mx-auto px-4 py-6 text-center text-gray-600 text-sm">
            <p>&copy; 2026 PBL Web Joglo66. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
