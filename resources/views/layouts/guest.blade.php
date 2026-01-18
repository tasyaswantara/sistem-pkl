<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @hasSection('title')
        @yield('title') | Sistem PKL
        @else
        Sistem PKL
        @endif
    </title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans text-gray-900 antialiased bg-slate-100">
    <div class="relative min-h-screen flex items-center justify-center px-4 py-10 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 via-slate-50 to-teal-100"></div>
        <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-emerald-200/40 blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 w-80 h-80 rounded-full bg-teal-200/40 blur-3xl"></div>

        <div class="relative w-full max-w-md">
            <div class="flex justify-center mb-6">
                <a href="/" class="inline-flex items-center gap-3">
                    <img src="{{ asset('assets/images/logo serviam.jpg') }}" alt="Logo Serviam" class="w-14 h-14 rounded-xl object-cover shadow-sm">
                    <div>
                        <div class="text-lg font-semibold text-gray-900">Sistem PKL</div>
                        <div class="text-xs text-gray-500">Portal Administrasi</div>
                    </div>
                </a>
            </div>

            <div class="bg-white/90 backdrop-blur rounded-2xl border border-white shadow-lg px-6 py-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>

</html>
