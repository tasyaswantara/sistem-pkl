<!DOCTYPE html>
<html lang="{{ $page->language ?? 'en' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="referrer" content="always">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div x-data="{ sidebarOpen: false }" class="flex h-screen bg-gray-200 font-roboto">
        @include('layouts.admin.sidebar')

        <div class="flex-1 flex flex-col overflow-hidden">
            @include('layouts.admin.header')

            <main class="flex-1 min-h-screen pb-[10vh] relative overflow-x-hidden overflow-y-auto
             bg-gradient-to-br
             from-gray-50
             via-emerald-50/30
             to-teal-50/40">

                <!-- Decorative background elements -->
                <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
                    <div class="absolute top-0 right-0 w-[500px] h-[500px]
                    bg-emerald-200/20 rounded-full blur-3xl"></div>

                    <div class="absolute bottom-0 left-[240px] w-[400px] h-[400px]
                    bg-teal-200/20 rounded-full blur-3xl"></div>
                </div>

                <div class="relative z-10 container mx-auto px-6 py-8">
                    {{ $slot }}
                </div>

            </main>
        </div>
    </div>
</body>

</html>