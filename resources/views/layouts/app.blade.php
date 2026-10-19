<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @stack('meta')
    <title>@yield('title', $company->name)</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-900 antialiased min-h-screen flex flex-col">
    @php
        $navLinks = [
            ['route' => 'home', 'label' => 'Home'],
            ['route' => 'about', 'label' => 'About'],
            ['route' => 'projects', 'label' => 'Projects'],
            ['route' => 'contact', 'label' => 'Contact Us'],
        ];
    @endphp

    <nav class="bg-gray-900 text-white" aria-label="Main">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-2 sm:py-4">
            <div class="flex items-center justify-between">
                <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center text-xl font-semibold tracking-tight text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                    {{ $company->name }}
                </a>

                <div class="hidden sm:flex items-center gap-8">
                    @foreach ($navLinks as $link)
                        <a
                            href="{{ route($link['route']) }}"
                            @if (request()->routeIs($link['route'])) aria-current="page" @endif
                            class="inline-flex min-h-11 items-center text-sm {{ request()->routeIs($link['route']) ? 'text-white font-medium' : 'text-gray-400 hover:text-white' }} transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                        >
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>

                <details class="sm:hidden relative group">
                    <summary aria-label="Menu" class="list-none [&::-webkit-details-marker]:hidden inline-flex items-center justify-center min-h-11 min-w-11 rounded-md text-gray-300 hover:text-white cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                        <svg class="h-6 w-6 group-open:hidden" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                        </svg>
                        <svg class="hidden h-6 w-6 group-open:block" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </summary>

                    <div class="absolute right-0 mt-2 w-56 rounded-lg bg-gray-900 ring-1 ring-white/10 py-2 shadow-lg z-50">
                        @foreach ($navLinks as $link)
                            <a
                                href="{{ route($link['route']) }}"
                                @if (request()->routeIs($link['route'])) aria-current="page" @endif
                                class="block px-4 py-3 text-base {{ request()->routeIs($link['route']) ? 'text-white font-medium' : 'text-gray-400 hover:text-white' }} transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-white"
                            >
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </div>
                </details>
            </div>
        </div>
    </nav>

    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="bg-gray-900 text-white mt-auto">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
            <div class="flex flex-col gap-2 text-center sm:flex-row sm:items-center sm:justify-between sm:text-left">
                <span class="text-lg font-semibold">{{ $company->name }}</span>
                <p class="text-sm text-gray-400">
                    &copy; {{ date('Y') }} {{ $company->name }}. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
