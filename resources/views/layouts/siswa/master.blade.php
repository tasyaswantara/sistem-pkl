<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/images/logo serviam.jpg') }}">

    <title>
        @hasSection('title')
            @yield('title') | Sistem PKL
        @else
            Sistem PKL
        @endif
    </title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="min-h-screen bg-gradient-to-br from-emerald-100 via-slate-50 to-teal-100 text-gray-900 font-roboto">
    @php
        $menus = [
            ['label' => 'Beranda', 'route' => 'siswa.dashboard'],
            ['label' => 'Presensi', 'route' => 'siswa.presensi'],
        ];
    @endphp

    <header class="sticky top-0 z-30 bg-white/95 shadow-sm backdrop-blur">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between gap-4">
                <a href="{{ route('siswa.dashboard') }}" class="flex items-center gap-3">
                    <div class="h-10 w-10 overflow-hidden rounded-lg border border-emerald-100 bg-emerald-50 shadow-sm">
                        <img src="{{ asset('assets/images/logo serviam.jpg') }}" alt="Logo"
                            class="h-full w-full object-cover">
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-emerald-700">Sistem PKL</p>
                        <p class="text-sm font-semibold text-emerald-900">Portal Siswa</p>
                    </div>
                </a>

                {{-- <nav class="hidden items-center gap-1 md:flex">
                    @foreach ($menus as $menu)
                        <a href="{{ route($menu['route']) }}"
                            class="rounded-lg px-3 py-2 text-sm transition {{ request()->routeIs($menu['route']) ? 'bg-emerald-600 text-white' : 'text-gray-600 hover:bg-emerald-50 hover:text-emerald-700' }}">
                            {{ $menu['label'] }}
                        </a>
                    @endforeach
                </nav> --}}

                <div class="flex items-center gap-2">
                    <span class="hidden text-sm text-gray-600 sm:block">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg px-3 py-2 text-sm text-emerald-700 hover:scale-125">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 17l5-5-5-5M21 12H9" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

            {{-- <nav class="scrollbar-none -mx-4 flex gap-2 overflow-x-auto px-4 pb-3 md:hidden">
                @foreach ($menus as $menu)
                    <a href="{{ route($menu['route']) }}"
                        class="whitespace-nowrap rounded-lg px-3 py-2 text-sm transition {{ request()->routeIs($menu['route']) ? 'bg-emerald-600 text-white' : 'bg-white text-gray-600 border border-gray-200' }}">
                        {{ $menu['label'] }}
                    </a>
                @endforeach
            </nav> --}}
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        {{ $slot }}
    </main>

    @stack('scripts')
</body>

</html>
