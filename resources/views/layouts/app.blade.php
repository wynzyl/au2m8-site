<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Au2m8'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-900 antialiased min-h-screen flex flex-col">
    <nav class="bg-gray-900 text-white">
        <div class="max-w-5xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <a href="{{ route('home') }}" class="text-xl font-semibold tracking-tight text-white">
                    Au2m8
                </a>
                <div class="flex items-center gap-8">
                    <a href="{{ route('home') }}" class="text-sm {{ request()->routeIs('home') ? 'text-white font-medium' : 'text-gray-400 hover:text-white' }} transition-colors">
                        Home
                    </a>
                    <a href="{{ route('about') }}" class="text-sm {{ request()->routeIs('about') ? 'text-white font-medium' : 'text-gray-400 hover:text-white' }} transition-colors">
                        About
                    </a>
                    <a href="{{ route('projects') }}" class="text-sm {{ request()->routeIs('projects') ? 'text-white font-medium' : 'text-gray-400 hover:text-white' }} transition-colors">
                        Projects
                    </a>
                    <a href="{{ route('contact') }}" class="text-sm {{ request()->routeIs('contact') ? 'text-white font-medium' : 'text-gray-400 hover:text-white' }} transition-colors">
                        Contact Us
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="bg-gray-900 text-white mt-auto">
        <div class="max-w-5xl mx-auto px-6 py-8">
            <div class="flex items-center justify-between">
                <span class="text-lg font-semibold">Au2m8</span>
                <p class="text-sm text-gray-400">
                    &copy; {{ date('Y') }} Au2m8. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
